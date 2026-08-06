## Context

`JsonResource::getRelationsData()` builds the `relationships` block by looping over `$includedRelations`, calling the matching `includeXxx()` method, and skipping the key entirely when that method returns `null`. The client therefore cannot tell an empty relationship from an ignored include.

Three facts about the current implementation shape every decision below. All three were verified against 14.0.1 at runtime, not inferred from reading.

1. **A populated relationship already carries a nested `data` wrapper.** `getRelationsData()` stores the child resource *object*, which is serialized later when `json_encode` triggers its own `jsonSerialize()`, at which point the child applies its own `$dataWrapper`. A populated relationship is read at `relationships.<name>.data`, never at `relationships.<name>`.
2. **The target shape already exists for one of the two empty idioms.** Laravel's `ConditionallyLoadsAttributes::filter()` contains `if ($value instanceof self && is_null($value->resource)) { $data[$key] = null; }`. An `includeAvatar()` returning `new AvatarResource(null)` therefore renders `"avatar": null` today, with no package change. Only the `return null` idiom drops the key.
3. **The package's own test helper cannot express the fixed shape.** `DataStructuresProvider` emits a relation as `$structure['relationships'][$name]['data']`, and `Response::assertJsonDataResource()` defaults to `$exact = true`, delegating to Laravel's `assertExactJsonStructure()`, which asserts key-set equality per level. A response carrying `relationships.avatar = null` cannot satisfy a structure demanding `relationships.avatar.data`, and a consumer who omits the relation to make today's test pass will newly fail on the unexpected key.

Fact 3 is why this change cannot be the one-line fix it first appears to be.

## Goals / Non-Goals

**Goals:**

- A relationship the client requested always appears as a key, so key presence alone distinguishes "resolved and empty" from "not served".
- The two ways of writing an empty relationship produce identical output.
- The package's own assertions can express a nullable relation under both exact and non-exact matching.
- The release records the break clearly enough that a consumer can plan the upgrade.

**Non-Goals:**

- Always-on relationships (default includes). The interpretive argument does not apply to something the client did not request, and rendering them would enlarge every payload for no gain.
- Empty collection-returning includes. A collection is never `null`, so this change does not reach them. Whether an empty collection should render `[]` is the same argument and is deliberately a separate change.
- Changing the nested `data` wrapper on populated relationships. It is longstanding behaviour and reshaping it would be a far larger break.
- `TransformsData.php`, whose own `continue` statements govern attribute transformation, not relationships.

## Decisions

### Decision 1: render plain `null`, not a shaped `{"data": null}` envelope

An empty relationship renders as `null` directly at `relationships.<name>`.

The alternative considered was an envelope mirroring the populated form, `{"data": null}`. It is genuinely attractive: it keeps the value's type stable across the empty and populated cases, it is closer to JSON:API convention, and, per fact 3, it would satisfy `DataStructuresProvider`'s existing `relationships.<name>.data` structure with no helper work at all.

It was rejected because of fact 2. The package already emits plain `null` for the `new XResource(null)` idiom. Choosing the envelope would leave two different renderings of emptiness depending on which idiom an `includeXxx()` happened to use, unless that path is also intercepted and rewrapped. That would mean changing behaviour that is currently correct in order to accommodate a test helper. Adapting the helper to the wire format is the right direction; reshaping the wire format to suit the helper is not.

The cost is accepted knowingly: plain `null` is not type-stable, and it obliges Decisions 3 and 4.

An earlier draft justified plain `null` as "simpler for clients, `relationships.avatar == null` rather than `relationships.avatar.data == null`". That argument is withdrawn: per fact 1, `relationships.avatar.data` is already what clients read when the relationship is populated. Consistency with fact 2 is the reason, not simplicity.

### Decision 2: assign before `continue`, keep the guard

In `getRelationsData()`, the null branch assigns `$relations[$currentRelation] = null;` and then continues. The `continue` must remain rather than being restructured into an if/else, because `withRelations()` is called on the resource immediately afterwards and calling it on `null` is a fatal error. Keeping the early exit also preserves the existing shape of the loop, so the diff stays reviewable.

The `@var` annotation above the call is corrected to `...\JsonResource|null`, since it currently asserts a non-nullable type that the very next line null-checks, and the method gains `@return array<string, \Longman\LaravelLodash\Http\Resources\JsonResource|null>`.

### Decision 3: mark nullable relations with a `?` suffix in the include grammar

`DataStructuresProvider`'s grammar gains `name?` for a nullable single relation, composable with the existing structure hint (`avatar?:AdminAvatar`) and with chaining (`accounts?.degree`).

The suffix position follows the existing `name[]` collection marker, so `parseSegment()` gains one more suffix check rather than a new parsing mode. Alternatives considered:

- **A `?name` prefix.** Rejected: it reads as a modifier on the whole segment including the structure hint, and it collides visually with nothing else in the grammar, whereas suffixes already mean "shape of this relation".
- **A separate `$nullableRelations` argument.** Rejected: it splits one declaration across two arguments, and merging two independent declarations of the same relation (already supported, order-independently) would need matching logic in both places.

Combining the collection and nullable markers (`roles[]?`, `roles?[]`) throws. Empty collections are a non-goal, so a nullable collection has no defined meaning, and silently accepting it would invite exactly the confusion this change exists to remove.

### Decision 4: carry nullability in the emitted structure via a `?`-suffixed key, stripped by `Response`

`applyRelationTree()` emits a nullable relation under the key `<name>?` instead of `<name>`. `Response` walks the `relationships` node before asserting, and for each `?`-suffixed key:

- if the actual value at `relationships.<name>` is `null`, it drops the declared subtree and asserts key presence separately, so an absent key still fails;
- otherwise it strips the `?` and validates the subtree normally.

Under exact matching, the nullable key counts as expected in both cases, so the surrounding key set is still matched exactly.

The marker is unambiguous because `parseSegment()` already rejects relation names containing bracket characters and will reject `?` in a name for the same reason, so no legitimate relation name can end in `?`.

Alternatives considered:

- **Return the structure plus a separate list of nullable paths.** Rejected: `DataStructuresProvider::__callStatic()` returns a plain array today, and changing its return type is a breaking API change for every consumer subclass, in a change that already breaks enough.
- **Re-derive nullability in `Response` by re-parsing the `$relations` argument it already receives.** This is genuinely close, since `assertJsonDataResource()` already takes `array $relations`. Rejected because nullability would then be known only at the combined-assertion entry point; a consumer calling `assertJsonDataItemStructure()` with a pre-resolved structure would silently lose it.

The accepted trade-off: a resolved structure containing a nullable relation now carries a marker that only `Response` understands. Passing such a structure to Laravel's raw `assertJsonStructure()` would assert against a key that does not exist in the response. This is documented rather than defended against, because these structures are already produced for `Response` to consume.

### Decision 5: release as a major, carry the CHANGELOG here and tag separately

The change is a widening: no key is removed and no existing value changes type. It is still `15.0.0`, because consumers who wrote around the omission would otherwise receive a reshaped API on a routine `composer update` with no signal.

An opt-in property (`protected bool $renderNullRelations = false;`) was considered as a way to ship in a minor. Rejected: it leaves the wrong behaviour as the default, which is what allowed the defect to persist across several majors, and it does not avoid the Decision 3 and 4 work anyway.

This change adds the `### Changed (BREAKING)` entry under `## [Unreleased]`, matching how 14.0.0 was written. Promoting the heading to `[15.0.0]` and tagging is a separate step, as it was for 14.0.1.

## Risks / Trade-offs

- **Consumer test suites break in bulk, and more of them than the response change alone suggests.** `assertJsonDataResource()` defaults to `$exact = true`, so every consumer test declaring a nullable relation needs the new marker. One consuming project has 90 affected `includeXxx()` methods across 38 resource classes. → The CHANGELOG entry must lead with this rather than with the response shape, since it is the larger migration. The marker is a mechanical, greppable edit.
- **Key absence permanently stops meaning "not served".** After this change, a missing key means only "not requested". → Acceptable and intended: the remaining signal for a bad include is the exception on an unknown `includeXxx()`, which this change deliberately leaves loud.
- **The `?`-suffixed key leaks if a structure is passed to raw `assertJsonStructure()`.** → Documented in the README and CHANGELOG. `Response` strips the marker on every path it owns.
- **Plain `null` is not type-stable, so strictly-typed clients and generated OpenAPI schemas need `nullable` at the relationship level.** → Called out explicitly in the release notes, including that the nullable point is `relationships.<name>`, not `relationships.<name>.data`.
- **Decisions 3 and 4 enlarge a change that reads like a one-liner**, increasing review surface and the chance of a defect in the helper rather than the fix. → The helper work is additive to the grammar and has its own scenarios in the `testing-utilities` delta, so it is independently testable.

## Migration Plan

1. Land the `JsonResource` change with its tests, including the scenario pinning that both empty idioms converge.
2. Land the grammar and `Response` changes with their tests. Order matters only in that step 2 is what makes step 1 assertable by consumers.
3. Add the CHANGELOG entry and the README wire-shape documentation.
4. Tag `15.0.0` as a separate step.

**Rollback:** the `JsonResource` change is a single branch and reverts cleanly. The grammar addition is additive and can stay behind without breaking anything, since a structure with no `?` markers behaves exactly as it does today.

**Consumer path, available before the release:** rewriting a nullable `includeXxx()` from a null-guarded ternary to `return new XResource($model->relation);` produces the target shape on 14.0.1 already, and remains correct after the upgrade. Consumers blocked on this can unblock without waiting for `15.0.0`.

## Open Questions

- Should `Response` also offer a way to assert that a nullable relation is specifically `null` (rather than "null or populated")? The specs here require the permissive form, which is what a shared structure needs. A stricter per-test assertion may be wanted once consumers migrate, but it is not needed for this change and is deliberately left out.
- `openspec/config.yaml`'s project summary still states `laravel/framework ^12.0` while `composer.json` requires `^13.0`. Unrelated drift, noted here so it is not lost; it belongs in its own change.

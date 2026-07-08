## Context

`Longman\LaravelLodash\Testing\Response` extends `Illuminate\Testing\TestResponse` with JSON envelope assertions (`data`, `meta.pagination`, success and error structures). Host applications set the envelope shape via the static `setSuccessResponseStructure()` / `setErrorResponseStructure()` and call the data assertions in their feature tests. `Testing\DataStructuresProvider` is an abstract base that apps subclass with static structure properties; its `__callStatic` resolves `getFooStructure($relations)` calls and grafts relation structures into `$item['relationships'][...]['data']` via late static binding, so the subclass resolves its own structures. The package's `Http\Resources\JsonResource` emits resources as `{id, type, attributes, relationships}` where `id` is `(string) $model->getKey()` (or `getUidString()` for `UuidAsPrimaryContract` models) and `type` defaults to the model's short class name or `getResourceTypeDefinition()`.

Current state, verified against the installed dependencies:

- `assertJsonDataCollectionStructure()` builds `['data' => [$data]]`. The framework's `AssertableJsonString::assertStructure()` treats numeric key `0` with an array value as "assert key `0` exists, recurse into item 0". Only the first row is validated.
- `DataStructuresProvider::includeNestedRelation()` has the same idiom twice: collection relation leaves assign `['data'][0]` and the traversal branch descends into `['data'][0]`. It also assigns rather than merges, so with overlapping relation paths (`['roles.permissions', 'roles']`) the later plain include replaces the auto-vivified subtree containing the earlier nested include.
- The include string format has silent parse failures: mid-path segments never parse `:` (so `'program:AdminProgram.faculty'` creates a relation key literally named `program:AdminProgram`), and the bracket parser (`trim($s, '[]')`) accepts `'[students:AdminStudent]'` but turns the equally plausible `'[students]:AdminStudent'` into a corrupt `students]` key with no error.
- `getItemStructure()` uses `is_callable(['static', $method])`, which emits `Deprecated: Use of "static" in callables` on PHP 8.4 (verified by execution); the package requires PHP ^8.4, so every structure resolution produces deprecation noise in downstream suites.
- Line 100 of `Response` accesses `$this->getDecodedContent()['data']` unguarded; a response without a `data` key raises a PHP "Undefined array key" warning before any assertion message.
- `AssertableJsonString::__construct()` accepts a decoded array, so assertions can be re-rooted at any subtree via the `$responseData` parameter of `assertJsonStructure()` / `assertExactJsonStructure()`.
- In exact mode, a structure level whose only key is `*` skips the key-set comparison at that level and applies exactness per element, so the collection wildcard composes with exact mode. Conversely, a literal `0` key in a structure fails exact mode against any multi-row collection (key set `[0]` vs `[0, 1, ...]`), which is why the DSP fix must ship together with exact mode.
- `phpunit/phpunit ^13.0` provides `Assert::assertIsList()`; Laravel 13 provides `TestResponse::assertExactJsonStructure()`.

## Goals / Non-Goals

**Goals:**

- Every row of a `data` collection is structure-validated, not just index 0; likewise every row of nested relationship collections built from DSP structures.
- A defined, loudly validated relation include grammar (dots and nested arrays over one internal tree) with order-independent merging of overlapping declarations.
- Failure messages for malformed envelopes are readable assertion failures, not PHP warnings.
- Opt-in exact key-set matching for the `data` subtree and, when pager or cursor meta is included, the `meta.pagination` subtree, leaving all other envelope keys loosely checked.
- Package-level combined resource assertions (exact by convention) that tie a response to a named DSP structure, the expected wire `type`, and concrete model identity (id / id set), reusable across host applications.
- First test coverage for the changed assertion and DSP paths.
- First README documentation for the Testing module (a `### Testing Helpers` subsection under `## Usage`), covering wiring, envelope registration, structure assertions with exact mode, DSP usage, and the resource assertions.

**Non-Goals:**

- Tests for untouched assertions (`assertJsonDataCount`, pagination and cursor value assertions, error, success, and validation structures).
- Fixing `getDecodedContent()` returning null on invalid JSON (pre-existing; the declared `array` return type raises a TypeError).
- Exact matching of the top-level envelope or of `meta` keys outside `pagination`.
- Changing the production-side relation include DSL (`Http\Resources\JsonResource::withRelations()` keeps its plain dotted strings; it never had structure names or collection markers).

## Decisions

1. **Wildcard fix in `Response`**: `$struct['data'] = [$data]` becomes `$struct['data'] = ['*' => $data]`, hitting the framework branch that iterates every element. Alternative considered: keeping `[$data]` and looping manually over decoded rows; rejected because the framework wildcard already implements exactly this with consistent failure messages.

2. **Guard chain** at the top of the collection method, each step with an explicit message, in this order:
   ```php
   $decoded = $this->getDecodedContent();

   PHPUnit::assertArrayHasKey('data', $decoded, 'Response does not contain a "data" key.');
   PHPUnit::assertNotEmpty($decoded['data'], 'Data collection is empty.');
   PHPUnit::assertIsList($decoded['data'], 'Data is not a list.');
   ```
   `assertNotEmpty` prevents the wildcard's vacuous pass on empty collections (preserving current behavior). `assertIsList` preserves the old form's incidental rejection of keyed maps (which previously failed on the missing key `0`) with a clean message instead of a confusing deep framework failure. Alternative considered: minimal diff without guards; rejected because a testing library's product is its failure messages.

3. **Exact mode as a trailing `bool $exact = false` parameter** on both structure methods, mirroring the framework's own `assertStructure(..., bool $exact)` idiom, rather than separate `assertExact*` method variants. Named arguments keep call sites readable (`exact: true`). Alternative considered: separate methods; rejected because they duplicate the pager and cursor flags and split discovery. New signatures:
   ```php
   public function assertJsonDataItemStructure(array $data, bool $exact = false): self

   public function assertJsonDataCollectionStructure(
       array $data,
       bool $includePagerMeta = true,
       bool $includeCursorMeta = false,
       bool $exact = false,
   ): self
   ```

4. **Fixed execution order; loose pass always runs.** The loose whole-response `assertJsonStructure()` runs before any exact pass: it validates the envelope and guarantees every key the exact passes re-root at exists (`data` in the item method, `meta.pagination` when a meta flag is set; in the collection method `data` presence is already covered by the guard chain). When `$exact` is true, re-rooted exact passes then run:
   - Item: `$this->assertExactJsonStructure($data, $decoded['data'])`
   - Collection: `$this->assertExactJsonStructure(['*' => $data], $decoded['data'])`
   - Collection with pager or cursor meta: `$this->assertExactJsonStructure($metaStructure, $decoded['meta']['pagination'])`

   Dropping the loose pass in exact mode would silently weaken envelope validation, since the re-rooted pass cannot see the envelope.

5. **Meta structure resolution collapses to one variable.** The pager and cursor conditionals produce a single `$metaStructure` reused by both the loose and the exact pass. Cursor continues to win when both meta flags are set (unchanged behavior). The item method gets no guard chain: its loose pass already asserts `data` presence with a clear framework message.

6. **Relation include grammar: dots and nested arrays over one internal tree.** Each segment is `name` or `name[]` (collection), optionally followed by `:StructureName` (canonical form `name[]:Structure`; `name:Structure[]` is rejected). Segments compose via dots for linear chains (`'roles[].admins[].item'`) and via nested arrays for branching (`'program:AdminProgram' => ['faculty:AdminFaculty', 'department:AdminDepartment']`), mirroring Laravel's `with()`, which accepts exactly this dual shape. All input normalizes into a single internal tree before any structure is emitted; the tree walker resolves each node's structure top-down and emits collection nodes under `['data']['*']` (required for exact-mode compatibility: a literal `0` key fails exact's key-set comparison against multi-row collections). Alternatives considered: pure nested tree (simplest internals but verbose linear chains and divergent from the Laravel dot habit); pure dot strings (terse chains but sibling branches repeat prefixes, and merge machinery is needed anyway).

7. **Merge semantics for overlapping declarations.** Declarations like `'roles[]'` alongside `'roles[].admins[].item'` merge order-independently at the normalized-tree level: children are unioned; an explicit structure name wins over the key-derived default; two different explicit names for the same relation throw `InvalidArgumentException`, as does the same relation marked `[]` in one declaration and unmarked in another. Malformed segments (the legacy `'[roles]'` wrapper, empty names, misplaced `:` or `[]`, a string key mapping to a string value like `'program' => 'AdminProgram'`) throw `InvalidArgumentException` with a migration hint instead of silently corrupting the structure. Nested children arrays accept the full grammar recursively, dots included. Alternative considered: rejecting duplicates outright; rejected because composing relation sets (a base set plus per-test additions) is a legitimate pattern that merging serves. Alternative considered: `array_merge_recursive` on emitted structures; rejected because it appends duplicate numeric-key list entries (`['id', 'id', 'name']`), which then break exact mode's key-set comparison — merging happens on the normalized tree, never on emitted structures.

   The rewrite removes the old `protected static includeNestedRelations()` / `includeNestedRelation()` methods outright (recorded under Changed (BREAKING)); deprecated shims would preserve exactly the semantics being eliminated. It also replaces `is_callable(['static', $method])` with explicit method/property resolution, fixing the PHP 8.4 deprecation.

   Implementation constraint discovered by the tests: PHP method names are case-insensitive, so internal helpers MUST NOT follow the `get*Structure` naming pattern. The old `getItemStructure()` helper collided with any relation named `item` (the built getter name `getitemStructure` dispatched to the helper itself with zero arguments instead of falling through to `__callStatic`); the helper is renamed `resolveItemStructure()` and resolves a real getter method first, then the structure property, without re-entering the magic dispatcher.

8. **Combined resource assertions, exact by default.** New API with no backward-compatibility constraint, matching the stated convention endgame (structure, wire type, and identity asserted at zero per-test cost):
   ```php
   public static function setDataStructuresProvider(?string $class): void

   public function assertJsonDataResource(
       string $structure,
       Model $model,
       array $relations = [],
       ?string $type = null,
       bool $exact = true,
   ): self

   public function assertJsonDataResources(
       string $structure,
       iterable $models,
       array $relations = [],
       ?string $type = null,
       bool $ordered = false,
       bool $includePagerMeta = true,
       bool $includeCursorMeta = false,
       bool $exact = true,
   ): self
   ```
   Resolution calls `$provider::get{$structure}Structure($relations)` through DSP's existing `__callStatic` and late static binding; the resolved array feeds the structure assertions from decision 3, so the guard chain, wildcard, and exact scoping compose. Calling a resource assertion without a registered provider throws `LogicException` (test-suite misconfiguration, not an assertion failure). Unknown structure names keep DSP's existing `InvalidArgumentException`.

9. **Identity and type semantics mirror the package's resource contract.** Expected id derives exactly as `Http\Resources\JsonResource::getResourceId()` does: `getUidString()` for `UuidAsPrimaryContract` models, else `(string) $model->getKey()`. Expected type is `$type ?? $structure` (name-equals-wire-type convention, overridable per call). Items assert `data.id` and `data.type`; collections assert `type` on every row and compare the id column with `assertEqualsCanonicalizing` (set semantics) or in sequence when `ordered: true`, which also implicitly asserts the row count. Alternative considered: configurable id/type paths; rejected because the `{id, type, attributes}` shape is the package's own `JsonResource` contract, not an app convention.

10. **Empty expected set is a first-class scoping proof.** `assertJsonDataResources($structure, [])` special-cases the empty set: it asserts the success envelope, asserts `data` is exactly `[]`, and still asserts `meta.pagination` when a meta flag is set (exact when `exact` is true), skipping only the vacuous per-row structure, type, and id checks. This serves "this user sees nothing" proofs directly instead of forcing a fallback to `assertJsonDataCount(0)`. The collection structure assertion itself keeps its non-empty guard; emptiness is only valid when it is the explicit expectation.

## Risks / Trade-offs

- [Downstream suites newly fail on heterogeneous rows or multi-row nested collections] → Intended behavior surfacing real drift; rides the pending major release where suites are being touched anyway. Recorded under Changed (BREAKING).
- [Downstream code consuming DSP structures positionally (expecting key `0`) breaks] → Recorded under Changed (BREAKING); the `'*'` key is the framework's own collection idiom.
- [Downstream call sites using the legacy `'[roles]'` wrapper throw after upgrading] → Intentional loud failure with a migration hint in the exception message; the mechanical fix is `'[roles]'` → `'roles[]'`. Plain dotted paths remain valid, so most call sites need no change.
- [Consumer subclasses overriding the two structure methods fatal on signature mismatch] → One-line note in the changelog; optional parameter addition is compatible for all callers.
- [Resources with conditional keys (`whenLoaded`, `mergeWhen`) fail under exact mode] → Documented adoption caveat; `exact: false` opt-out exists on all four methods.
- [An empty nested list inside a row passes exact mode vacuously] → Framework semantics for `*` levels; accepted.
- [Pager meta declares `links` as a bare string entry, so its internals are not exact-checked] → Follows the declared structure; cursor meta declares nested keys and is fully exact-checked.
- [Resource assertions assume the package's `{id, type, attributes}` shape] → By design; apps not using the package's `JsonResource` shape simply do not adopt these two methods.
- [Downstream DSP subclasses overriding or calling the removed protected include methods break] → Recorded under Changed (BREAKING); subclasses conventionally define structure properties only.
- [Polymorphic collections cannot use `assertJsonDataResources()` (`type` is always asserted against one name)] → Accepted; polymorphic endpoints use the plain structure assertions.
- [On paginated endpoints the id comparison covers the current page only] → Inherent to response-level assertions; documented in the README section.

## Migration Plan

Ships in the already-pending major release (the `[Unreleased]` changelog section carries the Laravel 13 requirement). No deployment steps; consumers adopt `exact: true` and the resource assertions per call site at their own pace. Rollback is reverting the release.

## Open Questions

None. All decisions were resolved in the pre-proposal design review and the follow-up scope interview.

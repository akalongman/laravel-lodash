## Why

`Testing\Response::assertJsonDataCollectionStructure()` builds `['data' => [$data]]`, which the framework interprets as "validate index 0 only", so rows 1..n of a collection are never structure-checked and heterogeneous rows pass silently. The same index-0 idiom appears in `DataStructuresProvider::includeNestedRelation()`, where collection relations emit `['data'][0]`, and relation grafting assigns instead of merging, so overlapping relation paths clobber each other order-dependently. Additionally, all structure assertions are superset checks with no opt-in exact mode, so response-shape drift (extra keys in resources or pagination meta) is invisible to tests, and there is no package-level assertion tying a response to a named resource structure plus the concrete model identity behind it. Laravel 13 ships `TestResponse::assertExactJsonStructure()`, and the pending major release is the right vehicle for the behavior tightening.

## What Changes

- **BREAKING**: `assertJsonDataCollectionStructure()` validates every row of `data` via the `['*' => $data]` wildcard instead of only index 0. Downstream suites that were hiding heterogeneous rows will newly fail; that is the assertion doing its job.
- **BREAKING**: `DataStructuresProvider` emits `['data']['*']` instead of `['data'][0]` for collection relations at every nesting level, so nested relationship collections validate every row and compose with exact mode. Structures consumed positionally by downstream code will see the key change.
- **BREAKING**: relation include declarations get a defined grammar. Segments are `name` or `name[]` (collection), each optionally `:StructureName`; segments combine via dots for linear chains (`'roles[].admins[].item'`) and nested arrays for branching (`'program:AdminProgram' => ['faculty:AdminFaculty']`), mirroring Laravel's `with()` shape. Overlapping declarations merge order-independently (children union; an explicit structure name wins over the key-derived default; conflicting explicit names or inconsistent `[]` markers throw). The old `'[roles]'` wrapper form and malformed segments now throw `InvalidArgumentException` with a migration hint instead of silently corrupting structures. The old `protected static includeNestedRelations()` / `includeNestedRelation()` methods are removed. Practical migration: `'[roles]'` becomes `'roles[]'`; plain dotted paths remain valid.
- The rewrite also replaces `is_callable(['static', $method])` with `method_exists(static::class, $method)` in structure resolution, fixing a `Deprecated: Use of "static" in callables` notice emitted on PHP 8.4.
- The collection assertion gains a guard chain with readable failure messages before any structure pass: `data` key present, `data` not empty, `data` is a list.
- New trailing parameter `bool $exact = false` on `assertJsonDataItemStructure()` and `assertJsonDataCollectionStructure()`. Exact mode re-roots `assertExactJsonStructure()` at the `data` subtree (and at `meta.pagination` when a meta flag is set) after the loose whole-response pass; envelope keys outside those subtrees are never exact-matched. Consumer subclasses overriding these two methods must update their signatures.
- New combined resource assertions on `Testing\Response`, exact by default (`exact: true`): `assertJsonDataResource(string $structure, Model $model, array $relations = [], ?string $type = null, bool $exact = true)` and `assertJsonDataResources(string $structure, iterable $models, array $relations = [], ?string $type = null, bool $ordered = false, bool $includePagerMeta = true, bool $includeCursorMeta = false, bool $exact = true)`. They resolve the named structure against a registered `DataStructuresProvider` subclass (`Response::setDataStructuresProvider()`), delegate to the structure assertions, assert `data.type` (defaulting to the structure name), and assert model identity: `data.id` for items, the id set (or sequence with `ordered: true`) for collections, deriving expected ids the same way `Http\Resources\JsonResource` does (`getUidString()` for UUID models, `(string) getKey()` otherwise). An empty model set is a first-class scoping proof: `assertJsonDataResources($structure, [])` asserts the envelope, `data` exactly `[]`, and pagination meta when included, skipping only the vacuous per-row checks.
- New `tests/Unit/Testing/ResponseTest.php` and `tests/Unit/Testing/DataStructuresProviderTest.php` covering the changed paths, the first tests for both classes.
- New `### Testing Helpers` subsection under the README's `## Usage` section, the first README documentation for the Testing module: wiring `Testing\Response` into a test case, envelope structure registration, the structure assertions with exact mode, `DataStructuresProvider` subclassing with relation includes, and the combined resource assertions.
- `CHANGELOG.md` `[Unreleased]`: wildcard and DSP key changes under Changed (BREAKING); guard messages, `exact` parameter, provider registration, and resource assertions under Added.

## Capabilities

### New Capabilities

- `testing-utilities`: first spec for this existing capability-map entry. Covers the JSON data structure assertions of `Testing\Response` (collection-wide validation, guard behavior, exact mode scoping), `DataStructuresProvider` relation grafting, and the combined resource assertions (structure resolution, type and identity checks).

### Modified Capabilities

<!-- none: testing-utilities has no existing spec under openspec/specs/ -->

## Impact

- Code: `src/Lodash/Testing/Response.php`, `src/Lodash/Testing/DataStructuresProvider.php`, `tests/Unit/Testing/ResponseTest.php` (new), `tests/Unit/Testing/DataStructuresProviderTest.php` (new), `README.md`, `CHANGELOG.md`.
- Dependencies: none added. `Assert::assertIsList()` (PHPUnit ^13.0), `assertExactJsonStructure()` (Laravel 13), and `Illuminate\Database\Eloquent\Model` are already available.
- Downstream: behavior tightening rides the pending major release (the `[Unreleased]` changelog already carries the Laravel 13 requirement), so consumers encounter it at a natural suite-fixing point. Resources with conditional keys (`whenLoaded`, `mergeWhen`) are expected to opt out with `exact: false` during adoption. The combined resource assertions assume the package's `{id, type, attributes, relationships}` resource shape emitted by `Http\Resources\JsonResource`.

## 1. Test scaffolding and red tests for the wildcard fix

- [ ] 1.1 Create `tests/Unit/Testing/ResponseTest.php` extending `Tests\Unit\TestCase`: `#[Test]` attributes with snake_case method names, a helper constructing `Longman\LaravelLodash\Testing\Response` around an `Illuminate\Http\JsonResponse` payload, and `setUp()` resetting `Response::setSuccessResponseStructure([])` (static process-wide state)
- [ ] 1.2 Add loose-mode collection tests: homogeneous rows pass and second-row-missing-a-key fails via `expectException(AssertionFailedError::class)`. Run against the unmodified `Response` and record that the second test is red, proving the pre-fix blind spot
- [ ] 1.3 Add guard-chain tests (red): response without a `data` key fails with `Response does not contain a "data" key.`, empty `data` fails with `Data collection is empty.`, keyed-map `data` fails with `Data is not a list.`

## 2. Implement wildcard fix and guard chain

- [ ] 2.1 In `assertJsonDataCollectionStructure()`: add the guard chain (`assertArrayHasKey` with `Response does not contain a "data" key.`, `assertNotEmpty` with `Data collection is empty.`, `assertIsList` with `Data is not a list.`) operating on a single `$decoded = $this->getDecodedContent()` read, replacing the unguarded line-100 access
- [ ] 2.2 Replace `$struct['data'] = [$data]` with `$struct['data'] = ['*' => $data]` and collapse the pager/cursor conditionals into a single `$metaStructure` variable (cursor wins when both flags are set, unchanged)
- [ ] 2.3 Run phase 1 tests: all green

## 3. Exact structure mode

- [ ] 3.1 Add exact-mode tests (red): extra key in one row fails under `exact: true` while the same payload passes loose (data and pager meta variants), extra top-level envelope key passes under exact with a configured success structure, item with extra and missing key fails under exact while loose passes, nested relation block recursion, extra key inside `meta.pagination.cursor` fails under exact with `includeCursorMeta: true`
- [ ] 3.2 Add trailing `bool $exact = false` to `assertJsonDataItemStructure()`; when true, after the loose pass run `$this->assertExactJsonStructure($data, $decoded['data'])`
- [ ] 3.3 Add trailing `bool $exact = false` to `assertJsonDataCollectionStructure()`; when true, after the loose pass run `$this->assertExactJsonStructure(['*' => $data], $decoded['data'])` and, when `$metaStructure` is set, `$this->assertExactJsonStructure($metaStructure, $decoded['meta']['pagination'])`
- [ ] 3.4 Run phases 1-3 tests: all green

## 4. DataStructuresProvider relation grammar

- [ ] 4.1 Create `tests/Unit/Testing/DataStructuresProviderTest.php` with a fixture `DataStructuresProvider` subclass defining several static structures (single and collection relations, structure names differing from relation keys)
- [ ] 4.2 Add grammar tests (red): dotted chain `'program:AdminProgram.faculty:AdminFaculty'` resolves identically to nested `'program:AdminProgram' => ['faculty:AdminFaculty']`, including dots inside nested children arrays; `'roles[].admins[].item'` nests `relationships.roles.data.*` containing `relationships.admins.data.*` containing `relationships.item.data`; collection includes emit `data.*`, never `data.0`
- [ ] 4.3 Add merge and validation tests (red): `['roles[]', 'roles[].admins[].item']` merges order-independently; explicit structure name wins over the key-derived default; conflicting explicit names throw `InvalidArgumentException`; inconsistent collection markers (`['roles', 'roles[].admins']`) throw; legacy `'[roles]'` throws with a `roles[]` migration hint; misplaced marker `'students:AdminStudent[]'` throws naming the canonical form; string value under a string key (`['program' => 'AdminProgram']`) throws hinting at `'program:AdminProgram'`
- [ ] 4.4 Add an integration test (red) in `ResponseTest`: a DSP-resolved structure with a collection relation asserted via `assertJsonDataCollectionStructure(..., exact: true)` passes against a response whose relationship collection has multiple conforming rows
- [ ] 4.5 Rewrite the include machinery in `DataStructuresProvider`: a normalizer parsing dots, nesting, `[]` suffixes, and `:Structure` mappings into one validated internal tree (malformed segments throw with migration hints); a merger unioning overlapping declarations (explicit name wins over default, conflicts throw); a top-down emitter resolving each node's structure and writing collection nodes under `['data']['*']`. Remove `includeNestedRelations()` / `includeNestedRelation()` outright and replace `is_callable(['static', $method])` with `method_exists(static::class, $method)` in `getItemStructure()` (PHP 8.4 deprecation)
- [ ] 4.6 Run phase 4 tests: all green

## 5. Combined resource assertions

- [ ] 5.1 Add resource assertion tests (red): `setDataStructuresProvider()` registration plus `assertJsonDataResource()` passing for a conforming item (structure exact by default, `data.type` defaults to the structure name, `data.id` matches `(string) $model->getKey()`); failures for wrong id, wrong type, and extra key without an explicit `exact` argument; `type:` override respected; `LogicException` when no provider is registered
- [ ] 5.2 Add collection resource tests (red): `assertJsonDataResources()` passes on the same id set in a different order by default, fails under `ordered: true` for that order, fails when a row's id is outside the expected set, and asserts `type` on every row; include a UUID-model case deriving the expected id via `getUidString()`; empty model set passes against `data === []` (envelope and pagination meta still validated) and fails against a non-empty `data`
- [ ] 5.3 Implement `Response::setDataStructuresProvider(string $class)` (static property alongside the existing structure statics, reset in the test `setUp()`)
- [ ] 5.4 Implement `assertJsonDataResource(string $structure, Model $model, array $relations = [], ?string $type = null, bool $exact = true)`: resolve via `$provider::get{$structure}Structure($relations)`, delegate to `assertJsonDataItemStructure()`, assert `data.type` and `data.id` (id derived as `getUidString()` for `UuidAsPrimaryContract`, else `(string) getKey()`)
- [ ] 5.5 Implement `assertJsonDataResources(string $structure, iterable $models, array $relations = [], ?string $type = null, bool $ordered = false, bool $includePagerMeta = true, bool $includeCursorMeta = false, bool $exact = true)`: delegate to `assertJsonDataCollectionStructure()`, assert `type` on every row, compare the id column via `assertEqualsCanonicalizing` (set) or in sequence when `ordered: true`; special-case an empty `$models`: assert the envelope, `data` exactly `[]`, and `meta.pagination` when a meta flag is set (exact when `exact`), skipping per-row checks
- [ ] 5.6 Run phase 5 tests: all green

## 6. Documentation and verification

- [ ] 6.1 Update `CHANGELOG.md` `[Unreleased]`: collection wildcard, DSP `['data']['*']` key changes, the relation include grammar (legacy `'[roles]'` wrapper now throws; migration `'[roles]'` → `'roles[]'`), and removal of the protected `includeNestedRelations()` / `includeNestedRelation()` methods under Changed (BREAKING), noting that previously only first items were validated and suites hiding heterogeneous rows will newly fail; guard-chain messages, `exact` parameter, `setDataStructuresProvider()`, and the two resource assertions under Added, with a one-line note that subclasses overriding the two structure methods must update signatures; the `is_callable(['static', ...])` PHP 8.4 deprecation fix under Fixed
- [ ] 6.2 Add a `### Testing Helpers` subsection under the README's `## Usage` section (and its Table of Contents entry): wiring `Testing\Response` into a test case via `createTestResponse()`, `setSuccessResponseStructure()` / `setErrorResponseStructure()` registration, the structure assertions with `exact:` examples, `DataStructuresProvider` subclassing with the relation include grammar (dots for chains, nested arrays for branching, `name[]` collections, `:Structure` mappings, merge behavior, and the `'[roles]'` → `'roles[]'` migration), and `setDataStructuresProvider()` plus the two combined resource assertions with `ordered:` and `type:` examples
- [ ] 6.3 Run `composer test` (entire suite green) and `composer phpcs` (no output); fix any style violations in the touched files

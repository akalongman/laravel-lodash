## 1. JsonResource relationship rendering

- [x] 1.1 Add test fixtures under `tests/Unit/Http/Resources/` for a parent resource exposing a single nullable include and a nested chain, with the include return value injectable so both empty idioms can be exercised
- [x] 1.2 Write failing tests for a requested include returning `null`, asserting key presence via `assertArrayHasKey` on the decoded `relationships` block, never a dotted-path null read
- [x] 1.3 Change the null branch of `getRelationsData()` to assign `null` then `continue`, correct the `@var` annotation to `\Longman\LaravelLodash\Http\Resources\JsonResource|null`, and add `@return array<string, \Longman\LaravelLodash\Http\Resources\JsonResource|null>`
- [x] 1.4 Add a test pinning that `return null` and `return new XResource(null)` produce identical output at the relationship key
- [x] 1.5 Add tests for the preserved invariants: populated relationship still renders at `relationships.<name>.data`, an all-empty relation set still emits the block, unrequested relations stay absent, a null parent drops its nested chain, an unknown include still throws, and a null underlying resource emits no block
- [x] 1.6 Add a test that a `JsonResourceCollection` renders `relationships.<name>` as `null` on every row

## 2. DataStructuresProvider nullable grammar

- [x] 2.1 Extend `parseSegment()` to recognise a trailing `?` on the relation name, returning nullability alongside name, structure hint and collection flag
- [x] 2.2 Throw `InvalidArgumentException` for segments combining the collection and nullable markers (`roles[]?`, `roles?[]`), with a message naming the conflict
- [x] 2.3 Throw `InvalidArgumentException` for `?` appearing anywhere other than as the trailing marker on the name
- [x] 2.4 Carry nullability through `normalizePath()`, `mergeRelationTrees()` and `applyRelationTree()`, throwing on conflicting nullability between two declarations of the same relation, matching the existing collection-marker rule
- [x] 2.5 Emit a nullable relation under the `<name>?` key in `applyRelationTree()`
- [x] 2.6 Add grammar tests in `tests/Unit/Testing/`: marker alone, marker with a structure hint, marker in a chain, combined-marker rejection, and order-independent merging of a nullable declaration

## 3. Response nullable relation assertions

- [x] 3.1 Add a pre-assertion walk over the declared `relationships` node that resolves `?`-suffixed keys against the actual decoded payload
- [x] 3.2 When the actual value is `null`, drop the declared subtree and assert key presence separately, failing with a message naming the missing relationship when the key is absent
- [x] 3.3 When the actual value is populated, strip the marker and validate the subtree against the declared structure as normal
- [x] 3.4 Ensure exact mode counts the nullable key as expected in both the null and populated cases, so the surrounding `data` key set is still matched exactly
- [x] 3.5 Wire the walk into `assertJsonDataResource()` and `assertJsonDataResources()`, applying it per row for collections
- [x] 3.6 Add tests for all five scenarios in the `testing-utilities` delta, including that a relation declared without the marker still fails against a `null` value

## 4. Documentation and release notes

- [x] 4.1 Add a `### Changed (BREAKING)` entry under `## [Unreleased]` in `CHANGELOG.md`, leading with the exact-mode assertion migration, then the response shape change, both breakage categories, and the pre-release workaround
- [x] 4.2 Document in `README.md` the populated (`relationships.<name>.data`) and empty (`relationships.<name>` is `null`) wire shapes, plus the `name?` marker and the constraint that marked structures must be asserted through `Testing\Response`

## 5. Verification

- [x] 5.1 `composer phpcs` produces no output
- [x] 5.2 `composer test` passes

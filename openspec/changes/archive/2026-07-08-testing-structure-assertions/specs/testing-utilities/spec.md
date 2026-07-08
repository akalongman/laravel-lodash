## ADDED Requirements

### Requirement: Collection structure assertions validate every row
`Testing\Response::assertJsonDataCollectionStructure()` SHALL validate the given item structure against every element of the response's `data` array, using the framework's `*` wildcard, not only the first element.

#### Scenario: Homogeneous collection passes
- **WHEN** every element of `data` contains the asserted structure's keys
- **THEN** the assertion passes

#### Scenario: Later row missing a key fails
- **WHEN** the first element of `data` matches the structure but a later element is missing an asserted key
- **THEN** the assertion fails

### Requirement: Collection structure assertions guard the data envelope
`assertJsonDataCollectionStructure()` SHALL run a guard chain before any structure validation, failing with a readable assertion message rather than a PHP warning or a deep framework error.

#### Scenario: Response without a data key
- **WHEN** the decoded response has no `data` key
- **THEN** the assertion fails with the message `Response does not contain a "data" key.`

#### Scenario: Empty data collection
- **WHEN** the decoded response's `data` is an empty array
- **THEN** the assertion fails with the message `Data collection is empty.`

#### Scenario: Data is a keyed map
- **WHEN** the decoded response's `data` is a non-empty associative array rather than a list
- **THEN** the assertion fails with the message `Data is not a list.`

### Requirement: Exact structure mode for data assertions
`assertJsonDataItemStructure()` and `assertJsonDataCollectionStructure()` SHALL accept a trailing `bool $exact = false` parameter. When `$exact` is true, the methods SHALL assert, in addition to (not instead of) the loose whole-response structure validation, that the key set of the `data` subtree exactly matches the given structure at every nested level (for collections, per element). Envelope keys outside the re-rooted subtrees SHALL NOT be exact-matched.

#### Scenario: Extra key in a collection row under exact mode
- **WHEN** `assertJsonDataCollectionStructure($structure, exact: true)` runs against a response where any element of `data` contains a key absent from the structure
- **THEN** the assertion fails

#### Scenario: Extra key in a collection row under loose mode
- **WHEN** the same response is asserted without `exact`
- **THEN** the assertion passes

#### Scenario: Extra top-level envelope key under exact mode
- **WHEN** `exact: true` is used and the response contains an additional top-level key not present in the configured envelope structure, while `data` conforms exactly
- **THEN** the assertion passes

#### Scenario: Item with extra or missing key under exact mode
- **WHEN** `assertJsonDataItemStructure($structure, exact: true)` runs against a response whose `data` object has an extra key or lacks an asserted key
- **THEN** the assertion fails

#### Scenario: Exactness recurses into nested structures
- **WHEN** the asserted structure declares a nested block and a row's nested block contains an extra key
- **THEN** the assertion fails under `exact: true`

### Requirement: Exact mode covers pagination meta
When `$exact` is true and pager or cursor meta is included, `assertJsonDataCollectionStructure()` SHALL exact-match `meta.pagination` against the corresponding declared meta structure (cursor meta SHALL take precedence when both flags are set, matching loose-mode behavior).

#### Scenario: Extra key in pager pagination meta under exact mode
- **WHEN** `exact: true` with pager meta included runs against a response whose `meta.pagination` contains a key absent from the pager meta structure
- **THEN** the assertion fails

#### Scenario: Extra key in cursor pagination meta under exact mode
- **WHEN** `exact: true` with cursor meta included runs against a response whose `meta.pagination.cursor` contains a key absent from the cursor meta structure
- **THEN** the assertion fails

### Requirement: Relation include grammar
`DataStructuresProvider` SHALL accept relation includes where each segment is `name` or `name[]` (collection), optionally followed by `:StructureName`, composed via dots for linear chains and via nested arrays for branching. All accepted forms SHALL normalize into one internal tree before structures are emitted. Malformed segments, including the legacy `[name]` wrapper form, SHALL throw `InvalidArgumentException` with a migration hint.

#### Scenario: Dotted chain and nested array are equivalent
- **WHEN** a structure is resolved with `['program:AdminProgram.faculty:AdminFaculty']` and with `['program:AdminProgram' => ['faculty:AdminFaculty']]`
- **THEN** both resolve to the identical structure

#### Scenario: Collections at every chain level
- **WHEN** a structure is resolved with `['roles[].admins[].item']`
- **THEN** the resolved structure nests `relationships.roles.data.*`, containing `relationships.admins.data.*`, containing `relationships.item.data`

#### Scenario: Legacy wrapper form throws
- **WHEN** a structure is resolved with `['[roles]']`
- **THEN** an `InvalidArgumentException` is thrown whose message hints at the `roles[]` replacement

#### Scenario: Misplaced collection marker throws
- **WHEN** a structure is resolved with `['students:AdminStudent[]']`
- **THEN** an `InvalidArgumentException` is thrown naming the canonical `students[]:AdminStudent` form

#### Scenario: String value under a string key throws
- **WHEN** a structure is resolved with `['program' => 'AdminProgram']`
- **THEN** an `InvalidArgumentException` is thrown hinting at the `'program:AdminProgram'` form

### Requirement: Data structure provider collection relations use the wildcard
`DataStructuresProvider` SHALL emit collection relation structures under `['data']['*']` (not `['data'][0]`) at every nesting level, so that every row of a nested relationship collection is validated and the structure composes with exact mode.

#### Scenario: Collection relation structure keys
- **WHEN** a structure is resolved with a collection relation include such as `roles[]`
- **THEN** the resolved structure contains `relationships.roles.data.*` rather than `relationships.roles.data.0`

#### Scenario: Multi-row nested collection under exact mode
- **WHEN** a structure resolved with a collection relation include is asserted with `exact: true` against a response whose relationship collection contains more than one conforming row
- **THEN** the assertion passes

### Requirement: Overlapping relation declarations merge order-independently
`DataStructuresProvider` SHALL merge overlapping relation declarations on the normalized tree: children are unioned and an explicit structure name SHALL win over the key-derived default, regardless of declaration order. Two different explicit structure names for the same relation, or the same relation marked as a collection in one declaration and not in another, SHALL throw `InvalidArgumentException`.

#### Scenario: Leaf and deep chain through the same collection
- **WHEN** a structure is resolved with `['roles[]', 'roles[].admins[].item']`
- **THEN** the resolved structure contains the `roles` structure with its nested `admins` collection and `item` relationship, identical to resolving the declarations in reverse order

#### Scenario: Explicit structure name wins over default
- **WHEN** a structure is resolved with `['roles[]', 'roles[]:AdminRole']`
- **THEN** the `roles` relation uses the `AdminRole` structure

#### Scenario: Conflicting explicit structure names throw
- **WHEN** a structure is resolved with `['roles[]:AdminRole', 'roles[]:PublicRole']`
- **THEN** an `InvalidArgumentException` is thrown

#### Scenario: Inconsistent collection markers throw
- **WHEN** a structure is resolved with `['roles', 'roles[].admins']`
- **THEN** an `InvalidArgumentException` is thrown

### Requirement: Combined resource assertions
`Testing\Response` SHALL provide `assertJsonDataResource(string $structure, Model $model, array $relations = [], ?string $type = null, bool $exact = true)` and `assertJsonDataResources(string $structure, iterable $models, array $relations = [], ?string $type = null, bool $ordered = false, bool $includePagerMeta = true, bool $includeCursorMeta = false, bool $exact = true)`. Both SHALL resolve the named structure against the provider class registered via `Response::setDataStructuresProvider()`, delegate to the corresponding structure assertion (exact by default), and assert the wire type (`$type`, defaulting to the structure name) on the item or on every row. Calling either method without a registered provider SHALL throw `LogicException`.

#### Scenario: Conforming item resource
- **WHEN** `assertJsonDataResource('User', $user)` runs against a response whose `data` matches the resolved `User` structure exactly, with `data.type` equal to `User` and `data.id` equal to the model's identity
- **THEN** the assertion passes

#### Scenario: Wire type mismatch
- **WHEN** the response's `data.type` differs from the expected type
- **THEN** the assertion fails

#### Scenario: Explicit type override
- **WHEN** `type: 'AdminUser'` is passed and the response's `data.type` equals `AdminUser`
- **THEN** the type assertion uses the override instead of the structure name

#### Scenario: Extra key under the exact default
- **WHEN** `assertJsonDataResource()` is called without an explicit `exact` argument against a response whose `data` contains a key absent from the resolved structure
- **THEN** the assertion fails

#### Scenario: No provider registered
- **WHEN** a resource assertion is called before `Response::setDataStructuresProvider()` has been configured
- **THEN** a `LogicException` is thrown

### Requirement: Resource identity assertions
Resource assertions SHALL derive the expected id exactly as `Http\Resources\JsonResource::getResourceId()` does: `getUidString()` for `UuidAsPrimaryContract` models, otherwise `(string) $model->getKey()`. `assertJsonDataResource()` SHALL assert `data.id` equals the expected id. `assertJsonDataResources()` SHALL assert the `id` column of `data` equals the expected ids of the given models as a set by default, or as an ordered sequence when `ordered: true`; both comparisons implicitly assert the row count.

#### Scenario: Item identity mismatch
- **WHEN** `assertJsonDataResource()` runs against a response whose `data.id` differs from the model's expected id
- **THEN** the assertion fails

#### Scenario: Collection identity as a set
- **WHEN** `assertJsonDataResources()` runs against a response containing exactly the given models' ids in a different order, without `ordered`
- **THEN** the assertion passes

#### Scenario: Collection identity ordered
- **WHEN** `ordered: true` and the response's rows are not in the same sequence as the given models
- **THEN** the assertion fails

#### Scenario: Row leaked from outside the expected set
- **WHEN** the response contains a row whose id is not among the given models' ids
- **THEN** the assertion fails

#### Scenario: Empty expected set as a scoping proof
- **WHEN** `assertJsonDataResources()` is called with an empty model set against a response whose `data` is exactly `[]`
- **THEN** the assertion passes, still validating the envelope and, when a meta flag is set, `meta.pagination`

#### Scenario: Empty expected set against a non-empty response
- **WHEN** `assertJsonDataResources()` is called with an empty model set against a response whose `data` contains any row
- **THEN** the assertion fails

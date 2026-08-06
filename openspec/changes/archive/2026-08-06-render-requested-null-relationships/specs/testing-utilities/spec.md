## MODIFIED Requirements

### Requirement: Relation include grammar
`DataStructuresProvider` SHALL accept relation includes where each segment is `name`, `name[]` (collection), or `name?` (nullable single relation), optionally followed by `:StructureName`, composed via dots for linear chains and via nested arrays for branching. All accepted forms SHALL normalize into one internal tree before structures are emitted. Malformed segments, including the legacy `[name]` wrapper form and any segment combining the collection and nullable markers, SHALL throw `InvalidArgumentException` with a migration hint.

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

#### Scenario: Nullable marker on a single relation
- **WHEN** a structure is resolved with `['avatar?']` or `['avatar?:AdminAvatar']`
- **THEN** the relation is normalized as nullable, carrying the same structure it would carry without the marker

#### Scenario: Nullable marker composes in a chain
- **WHEN** a structure is resolved with `['accounts?.degree']`
- **THEN** `accounts` is normalized as nullable and retains `degree` as its child

#### Scenario: Collection and nullable markers combined throw
- **WHEN** a structure is resolved with `['roles[]?']` or `['roles?[]']`
- **THEN** an `InvalidArgumentException` is thrown stating that a collection relation cannot be nullable

## ADDED Requirements

### Requirement: Nullable relation declarations accept null or the populated subtree
`Testing\Response` resource assertions SHALL treat a relation declared nullable as satisfied by either the populated relationship subtree or an explicit `null` at `relationships.<name>`, under both exact and non-exact matching. A relation declared nullable SHALL still require the key to be present; an absent `relationships.<name>` key SHALL fail.

This closes a gap that would otherwise make the new empty-relationship wire shape unassertable: a nullable relation renders as `null` at `relationships.<name>` while a populated one renders at `relationships.<name>.data`, and a single declared structure must accept both.

#### Scenario: Nullable relation is null in the response
- **WHEN** a structure declaring `avatar?` is asserted against a response whose `relationships.avatar` is `null`
- **THEN** the assertion passes

#### Scenario: Nullable relation is populated in the response
- **WHEN** a structure declaring `avatar?` is asserted against a response whose `relationships.avatar.data` contains a conforming avatar
- **THEN** the assertion passes, and the avatar subtree is validated against the declared structure

#### Scenario: Nullable relation key is absent
- **WHEN** a structure declaring `avatar?` is asserted against a response containing no `relationships.avatar` key
- **THEN** the assertion fails with a message naming the missing relationship

#### Scenario: Nullable relation under exact mode
- **WHEN** a structure declaring `avatar?` is asserted with `exact: true` against a response whose `relationships.avatar` is `null`
- **THEN** the assertion passes and the surrounding `data` key set is still matched exactly

#### Scenario: Non-nullable relation that renders null still fails
- **WHEN** a structure declaring `avatar` without the nullable marker is asserted against a response whose `relationships.avatar` is `null`
- **THEN** the assertion fails, so that an unintended empty relationship is not silently accepted

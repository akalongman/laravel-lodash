# http-resources Specification

## Purpose

The `http-resources` capability covers the package's API resource layer under `Longman\LaravelLodash\Http\Resources`: `JsonResource` and its collection, plus the `ResourceResponse` and `PaginatedResourceResponse` that render them. This specification defines relationship rendering in particular: which relationships appear in a resource's `relationships` block, how a requested-but-empty relationship is represented on the wire, and which failures must stay loud. A relationship is opt-in, requested by the client through the include mechanism and served by a matching `include<Name>()` method on the resource.

## Requirements

### Requirement: Requested relationships are always represented
`JsonResource` SHALL render a key in the `relationships` block for every relationship present in `$includedRelations`, whether that relationship resolves to a resource or to nothing. A client SHALL be able to distinguish "resolved, and it is empty" from "not served" by key presence alone.

#### Scenario: Requested relationship resolves to null
- **WHEN** a client requests `?include=avatar` and `includeAvatar()` returns `null`
- **THEN** the response contains the key `relationships.avatar` with the value `null`

#### Scenario: Requested relationship resolves to a resource
- **WHEN** a client requests `?include=avatar` and `includeAvatar()` returns an avatar resource
- **THEN** the response renders the avatar at `relationships.avatar.data`

#### Scenario: Both ways of expressing emptiness converge
- **WHEN** one resource's `includeAvatar()` returns `null` and another's returns a resource wrapping a null model, both requested with `?include=avatar`
- **THEN** both responses render `relationships.avatar` as `null`, and the two payloads are indistinguishable at that key

#### Scenario: Every row of a collection carries the empty relationship
- **WHEN** a collection endpoint is requested with `?include=avatar` and every row's `includeAvatar()` returns `null`
- **THEN** every row under `data` contains `relationships.avatar` with the value `null`

### Requirement: Unrequested relationships remain absent
`JsonResource` SHALL NOT emit a key for a relationship the client did not request. Making requested-but-empty relationships explicit MUST NOT widen the payload for relationships absent from `$includedRelations`.

#### Scenario: No includes requested
- **WHEN** a client requests a resource without an `include` parameter
- **THEN** the response contains no `relationships` block

#### Scenario: Only the requested relationship appears
- **WHEN** a client requests `?include=avatar` on a resource that also defines `includeProfile()`
- **THEN** the response contains `relationships.avatar` and contains no `relationships.profile` key

### Requirement: The relationships block survives an all-empty relation set
`JsonResource` SHALL emit the `relationships` block when every requested relationship resolved to nothing. An array whose values are all `null` MUST NOT be treated as an empty relation set.

#### Scenario: Every requested relationship is empty
- **WHEN** a client requests `?include=avatar,profile` and both include methods return `null`
- **THEN** the response contains a `relationships` block in which `avatar` and `profile` are both `null`

### Requirement: Nested chains under an empty parent are dropped
When a requested relationship chain has a parent that resolves to nothing, `JsonResource` SHALL render that parent as `null` and SHALL NOT render any descendant of it. Nested relations SHALL NOT be resolved against an absent parent.

#### Scenario: Empty parent in a nested chain
- **WHEN** a client requests `?include=accounts.degree` and `includeAccounts()` returns `null`
- **THEN** the response renders `relationships.accounts` as `null` with no nested `degree` key anywhere beneath it

### Requirement: Unknown include names fail loudly
`JsonResource` SHALL throw when a requested relationship has no corresponding `includeXxx()` method. Making empty results explicit MUST NOT make invalid ones silent.

#### Scenario: Include name with no matching method
- **WHEN** a client requests an include whose `includeXxx()` method does not exist on the resource
- **THEN** an exception is thrown naming the missing method

### Requirement: Resources without an underlying model emit no relationships
`JsonResource` SHALL emit no `relationships` block when its underlying resource is null, regardless of which relationships were requested.

#### Scenario: Null underlying resource with includes requested
- **WHEN** a resource whose underlying model is `null` is rendered with `?include=avatar`
- **THEN** the response contains no `relationships` block

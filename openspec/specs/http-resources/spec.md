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

### Requirement: Include paths compose as a set, not a sequence

`JsonResource` SHALL derive its relation tree as the union of every path in
`$includedRelations`, independent of the order in which those paths appear. A
path that is a strict prefix of another path SHALL NOT discard that other path's
remaining segments. An ancestor path carries no instruction its descendant does
not already imply, so where the two coexist the more specific entry SHALL
survive regardless of position.

Callers assemble include lists by concatenating unrelated sources, typically
always-on defaults merged with client-supplied includes, and a set of include
paths carries no inherent order. Rendering MUST NOT depend on that ordering.

The order in which keys appear inside the rendered `relationships` object is NOT
part of this contract. Two include lists that are permutations of each other
SHALL render equal payloads, compared without regard to key order.

#### Scenario: Ancestor requested after its own descendant
- **WHEN** a resource is rendered with the includes `['accounts.degree', 'accounts']`
- **THEN** the response renders the nested resource at `relationships.accounts.data.relationships.degree.data`, exactly as it does for `['accounts', 'accounts.degree']`

#### Scenario: Defaults merged ahead of a client include that repeats one of them
- **WHEN** a caller merges the always-on defaults `['accounts', 'accounts.degree']` with a client-supplied `['accounts']`, yielding `['accounts', 'accounts.degree', 'accounts']`
- **THEN** the response still renders `relationships.accounts.data.relationships.degree`, and the nested relationship is not dropped

#### Scenario: Ancestor requested after a three-segment descendant
- **WHEN** a resource is rendered with the includes `['accounts.degree.faculty', 'accounts.degree']`
- **THEN** the full three-level chain renders, and neither the `degree` level nor the `faculty` level is lost

#### Scenario: Sibling branches under a shared parent
- **WHEN** a resource is rendered with the includes `['accounts.degree', 'accounts.avatar']`
- **THEN** both `degree` and `avatar` render beneath `accounts`, because sibling paths merge rather than replace

#### Scenario: The same path requested more than once
- **WHEN** a resource is rendered with the includes `['avatar', 'avatar']`
- **THEN** the response renders `relationships.avatar` exactly once, identically to the single-entry case

#### Scenario: Every ordering of one include list renders the same payload
- **WHEN** a resource is rendered once for each permutation of a fixed include list containing an ancestor, its descendant, and an unrelated top-level include
- **THEN** every permutation produces an equal payload, compared without regard to the order of keys within the `relationships` object

#### Scenario: An unknown include name still fails loudly regardless of position
- **WHEN** a resource is rendered with the includes `['accounts.degree', 'accounts', 'missing']` where no `includeMissing()` method exists
- **THEN** an exception is thrown naming the missing method, and the preserved ancestor path does not suppress the failure


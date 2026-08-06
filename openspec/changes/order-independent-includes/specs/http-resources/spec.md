## ADDED Requirements

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

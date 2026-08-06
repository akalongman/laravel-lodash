## Why

When a client requests a relationship through the include mechanism and that relationship resolves to nothing, `JsonResource::getRelationsData()` drops the key from the `relationships` block entirely. An absent key cannot distinguish "this record genuinely has no avatar" from "your include name was ignored", "the server declined to serve it", or "a bug dropped it". All four produce byte-identical responses, so the API has no way to say "I resolved what you asked for, and it is empty".

The defect is half-hidden, which is why it survived several majors. An `includeXxx()` method that returns `new AvatarResource(null)` already renders `"avatar": null` correctly today, because Laravel's `ConditionallyLoadsAttributes::filter()` collapses a resource wrapping a null model. Only the `return $model->avatar ? new AvatarResource(...) : null` idiom loses the key. One consuming project measured 90 nullable `includeXxx()` methods across 38 resource classes on the affected idiom.

## What Changes

- **BREAKING**: `JsonResource::getRelationsData()` renders a requested-but-empty relationship as `null` under its key instead of omitting the key. A populated relationship is still reached at `relationships.<name>.data`; an empty one is `null` at `relationships.<name>`.
- Both ways of expressing emptiness converge on one output. `return null` and `return new XResource(null)` become indistinguishable on the wire.
- Relationships that were never requested stay absent. The rule binds only entries in `$includedRelations`, which is what keeps opt-in includes worth having.
- `Testing\DataStructuresProvider` gains a nullable-relation marker in its include grammar, so a declared relationship node accepts either the populated subtree or `null`. This is additive to the grammar. Without it, the package's own exact-mode assertions cannot express the new wire shape at all.
- `Testing\Response::assertJsonDataResource()` and `assertJsonDataResources()` accept a structure containing a nullable relation under both exact and non-exact matching.
- `CHANGELOG.md` gains an entry under `## [Unreleased]` with a `### Changed (BREAKING)` heading, following the 14.0.0 style.
- `README.md` documents the populated and empty relationship wire shapes, which currently appear nowhere in it.

Explicitly out of scope: always-on relationships (default includes), empty collection-returning includes, the nested `data` wrapper on populated relationships, and `TransformsData.php`.

## Capabilities

### New Capabilities
- `http-resources`: relationship rendering in `JsonResource`, covering which relationships appear in the `relationships` block and how a requested-but-empty relationship is represented on the wire. This capability is already declared in `openspec/config.yaml`'s capability map and owns `JsonResource` and `ResourceResponse`, so no new-capability justification is required. It is listed here only because it has no spec file yet, this project letting specs emerge from real changes rather than stubbing them.

### Modified Capabilities
- `testing-utilities`: the include grammar consumed by `DataStructuresProvider` gains a nullable-relation marker alongside the existing `name[]` collection marker, and the `Response` resource assertions must accept a nullable relation under exact matching.

## Impact

**Code**

- `src/Lodash/Http/Resources/JsonResource.php`: the null branch in `getRelationsData()`, plus its `@var` and `@return` annotations.
- `src/Lodash/Testing/DataStructuresProvider.php`: include-grammar parsing and relationship structure emission.
- `src/Lodash/Testing/Response.php`: resource assertions under exact matching.
- `tests/Unit/Http/Resources/` and `tests/Unit/Testing/`.
- `CHANGELOG.md`, `README.md`.

**Consumers.** Two breakage categories, not one.

1. Client code branching on key presence (`array_key_exists`, `'avatar' in relationships`, `hasOwnProperty`). Code reading `relationships.avatar?.data?.url` is unaffected.
2. Test suites using this package's exact-mode resource assertions. `assertJsonDataResource()` defaults to `$exact = true`, and a consumer who omitted a nullable relation from the declared structure to make it pass on 14.0.x will newly fail on the unexpected key. In practice this is the larger group, and the plain "no key is removed" framing does not cover it.

**Versioning.** Release as major `15.0.0`. The change is a widening: no key is removed and no existing value changes type. It is still a major, because consumers who wrote around the omission would otherwise receive a reshaped API on a routine `composer update` with no signal. This change carries the CHANGELOG entry; tagging the release is a separate step.

**Migration available today.** Rewriting a nullable `includeXxx()` from a null-guarded ternary to `return new XResource($model->relation);` produces the target shape on 14.0.1 with no upgrade, and stays correct after this change. Consumers blocked on the fix can unblock without waiting for 15.0.0.

**Dependencies.** None. No composer constraint changes, no new packages.

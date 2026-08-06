## Why

`JsonResource::undot()` converts the flat list of dotted include paths into a
relation tree by calling `Arr::set()` once per path. `Arr::set()` assigns at the
final segment, discarding whatever was there, so a path that is a strict prefix
of an already-built path flattens that path's subtree. Given
`['criteria', 'criteria.courseComponent', 'criteria']` the method returns
`{"criteria": []}` and the nested include is silently gone.

That input shape is not exotic. It is what
`array_merge($defaultIncludes, $request->getValidatedIncludes())` produces the
moment a client asks for an include it already had by default. The result is the
worst shape a defect can take on a public API: requesting more data returns
less, with no error and no warning. The bug has been present in every release;
the 15.0.0 change that made an empty relationship render as an explicit `null`
did not cause it, it removed the camouflage that made a dropped relationship
indistinguishable from an empty one.

## What Changes

- `Http\Resources\JsonResource::undot()` guards its assignment so a shorter
  include path can no longer flatten a subtree an earlier, longer path already
  built. The resulting tree becomes the union of every input path, equal for any
  permutation of the input list.
- `http-resources` gains a requirement stating that the include set is
  order-independent, and stating explicitly that the order in which keys appear
  inside the rendered `relationships` object is NOT part of the contract.
- The relations test suite gains a permutation test driven through the public
  `withRelations()` surface, asserting that every ordering of one include list
  renders an equal payload, plus explicit cases for ancestor-after-descendant at
  two and three levels deep.
- `CHANGELOG.md` gains a `15.1.0` entry under `Fixed`, naming the
  `array_merge($defaults, $requested)` trap and warning that relationships which
  were previously dropped may newly appear.

Not breaking. This is strictly a widening: every input that previously produced
a complete tree still produces it, and only the paths that were being silently
discarded now survive. The one realistic breakage is a consumer whose test
asserted a nested relationship was absent under a prefix include, which was an
assertion on the defect.

## Capabilities

### New Capabilities

None. The change adds a requirement to an existing capability.

### Modified Capabilities

- `http-resources`: adds a requirement that the include-path set is
  order-independent and that the rendered tree is the union of every requested
  path. Existing requirements about which relationships appear, how an empty one
  is represented, and which failures stay loud are unchanged; this closes a gap
  none of them covered.

## Impact

- `src/Lodash/Http/Resources/JsonResource.php`: four added lines inside the
  private static `undot()`. No signature change, no new dependency, no change to
  any public or protected member, so no consumer resource subclass is affected.
- `tests/Unit/Http/Resources/JsonResourceRelationsTest.php`: new test cases.
  `tests/Unit/Http/Resources/RelationResource.php` may need an extra include
  method to express a three-level chain.
- `openspec/specs/http-resources/spec.md`: one new requirement, folded in at
  archive time.
- `CHANGELOG.md` and the release tag.
- Consumers: responses may newly carry nested relationships that were being
  dropped. Any consumer merging default includes ahead of client-supplied ones
  is affected, which is the standard pattern. Client code that reads a
  relationship defensively is unaffected.

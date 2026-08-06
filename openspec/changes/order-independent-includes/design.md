## Context

`JsonResource` resolves relationships from `$includedRelations`, a flat list of
dotted include paths. `getRelationsData()` turns that list into a tree via the
private static `undot()`, iterates the top level, calls the matching
`include<Name>()` method for each, re-flattens the remaining chain with
`Arr::dot()`, and hands it to the child resource through `withRelations()`.

`undot()` is four lines and calls `Arr::set()` once per path.
`Longman\LaravelLodash\Support\Arr` extends `Illuminate\Support\Arr`, so `set()`
is the framework's: it walks to the final segment and assigns, discarding
whatever occupied that position. Any path that is a strict prefix of an
already-built path therefore flattens that path's subtree.

Measured against the shipped method:

| Input | Output | Verdict |
|---|---|---|
| `['a', 'a.b']` | `{"a":{"b":[]}}` | correct |
| `['a.b', 'a']` | `{"a":[]}` | child lost |
| `['a', 'a.b', 'a']` | `{"a":[]}` | child lost, the real-world case |
| `['a.b', 'a.c']` | `{"a":{"b":[],"c":[]}}` | correct, siblings merge |
| `['a.b.c', 'a.b']` | `{"a":{"b":[]}}` | grandchild lost |
| `['a', 'a']` | `{"a":[]}` | correct, idempotent |

Two constraints shape the fix. First, `undot()` is `private static` and is the
sole reader of `$includedRelations`, verified by grep across `src/` and `tests/`;
nothing else in the package consumes that property. Second, `getRelationsData()`
recurses through `withRelations()`, which itself does
`array_merge($this->includedRelations, $relations)`. A child resource carrying
its own hardcoded `$includedRelations` therefore reproduces the identical merge
shape one level down, without any consumer code involved.

## Goals / Non-Goals

**Goals:**

- Make the derived relation tree the union of every include path, equal for any
  permutation of the input list.
- Fix it at a single point that covers every nesting level.
- Keep the change a widening: no input that previously produced a complete tree
  may produce a different one.
- Prove order-independence with a test that states the invariant rather than
  enumerating cases.

**Non-Goals:**

- Validating include names, warning on an include that no `include<Name>()`
  method serves, or otherwise changing the loud-failure behaviour. Unrelated,
  and the existing `http-resources` requirement already governs it.
- Deduplicating include lists before they reach `undot()`. That is a workaround
  for this defect, not a fix.
- Normalizing `$includedRelations` on ingress in `withRelations()`.
- Changing the include-validation layer in consuming applications, or the
  consumer-side follow-up work those applications need after upgrading. Tracked
  outside this repository.

## Decisions

### Guard the assignment inside `undot()`, using an emptiness test

```php
private static function undot(array $array): array
{
    $result = [];
    foreach ($array as $item) {
        if (! empty(Arr::get($result, $item))) {
            continue;
        }

        Arr::set($result, $item, []);
    }

    return $result;
}
```

`Arr::get()` returns three distinguishable things here: `null` for a path not yet
present, `[]` for a leaf this method set earlier, and a non-empty array for a
subtree an earlier longer path built. Only the third case must be protected, and
`empty()` is the test that collapses the first two into "safe to assign" while
isolating the third. Duplicates stay idempotent because a leaf is `[]`, which is
empty, so it is simply reassigned to the same value.

**The polarity matters and a strict comparison gets it wrong.** An earlier draft
of this fix guarded with `if (Arr::get($result, $item) !== []) { continue; }`,
reasoning that `[]` marks a leaf and anything else should be skipped. That guard
fires on `null` as well, so every path that is not already present is skipped and
the method returns `[]` for every input. Confirmed by running both variants
against the table above: the strict-comparison guard produced `[]` for all seven
cases. The guard must test emptiness, not equality with `[]`.

The guard's correctness is coupled to the leaf representation staying falsy.
`getRelationsData()` tests the remaining chain with `! empty(...)`, so `[]` is
already load-bearing in two places; if a future change gives a leaf a truthy
value, both must move together.

### Fix in `undot()` rather than `withRelations()`

`undot()` is the single choke point: every rendered relationship at every nesting
depth passes through it, including the recursive hand-off where the package
itself performs the offending `array_merge`. Normalizing in `withRelations()`
would put the guarantee on the public API, but it runs per call, including the
per-row loop in `JsonResourceCollection::withRelations()`, and it would leave
`undot()` still fragile for any future caller. Enforcing the invariant in the one
function whose job is building the tree keeps the rule and its enforcement in the
same place.

Considered and rejected:

- **Sort by depth before setting**
  (`usort(..., substr_count($a, '.') <=> substr_count($b, '.'))`). Applies
  shallower paths first so a descendant always extends an ancestor. Equivalent
  result, but it costs a sort on every render and mutates caller-visible
  ordering as a side effect of a fix that is supposed to be invisible.
- **Filter to maximal paths first**, keeping only paths that are not a strict
  prefix of another, then set unguarded. This is the cleanest statement of the
  invariant and belongs in the spec prose, but as code it is an O(n²) prefix
  comparison plus a second pass, to replace a two-line guard.
- **Hand-rolled trie walk** with a `&$ref` cursor descending segment by segment,
  creating each level only when absent. Structurally makes the defect
  unrepresentable rather than guarding against it, and is a single pass instead
  of two `Arr` walks per path. Rejected on idiom: the package consistently
  prefers framework helpers over reference gymnastics, and at include depths of
  one to three the traversal cost is noise.

### Preserve key order in practice, do not promise it in the spec

The guard leaves first-seen key order intact for disjoint top-level includes, so
nothing changes on the wire today. The spec nonetheless declares key order inside
the rendered `relationships` object outside the contract.

This resolves a contradiction that a naive reading of the requirement carries.
"For any permutation of the input the tree SHALL be identical" and "unrelated
top-level includes keep first-seen order" cannot both hold under PHP's `===`,
which is key-order sensitive on arrays: `['b', 'a']` yields `{b, a}` and
`['a', 'b']` yields `{a, b}`, the same tree with different key order. Declining
to promise key order makes the permutation requirement expressible, and leaves
room to adopt canonical ordering later if a consumer ever needs a byte-stable
payload.

The consequence for tests: permutation comparisons use `assertEquals`, whose
array semantics are PHP `==` and therefore blind to key order recursively.
`assertSame` would fail on a correct implementation.

### Test through the public surface, with permutations

New cases go in `tests/Unit/Http/Resources/JsonResourceRelationsTest.php` and
drive `withRelations()` through to the rendered JSON, matching how every existing
case in that file works. `undot()` stays private: no reflection, and no promotion
to `protected`, which would pull an implementation detail into the inheritance
contract for every consumer resource.

The load-bearing test renders one include list under every permutation and
asserts all renders are equal. It states the invariant instead of enumerating
cases, and it fails against the current implementation on most permutations.
Explicit cases still cover the two-level and three-level
ancestor-after-descendant shapes, since a permutation test over a small list can
pass while a deeper nesting bug survives.

### Release as `15.1.0`

A minor rather than a patch. The change repairs responses that were incomplete
rather than reshaping responses that were correct, which argues for a patch, but a
minor signals "read this before upgrading" more loudly. That matters because the
realistic breakage is in consumer test suites: `Testing\Response`'s resource
assertions default to `exact: true`, so a suite whose declared structure omitted a
relationship that was being dropped will newly fail on an unexpected key. The
changelog entry names the `array_merge($defaults, $requested)` trap explicitly,
because the ordering requirement is invisible at the call site and the next
author to write that line would otherwise reintroduce the problem.

## Risks / Trade-offs

- **Consumer suites fail on newly present relationship keys.** Their declared
  structures were passing only because the relationship was being dropped.
  Mitigation: the changelog entry states that recovered relationships may appear
  where they were previously absent, and points at the `?` nullable marker added
  in 15.0.0 as the way to declare them. A minor version bump carries the signal.

- **Permutation count grows factorially.** A permutation test over five include
  paths is 120 full renders. Mitigation: fix the list at three paths (an
  ancestor, its descendant, one unrelated top-level include), giving six renders,
  and cover depth with explicit cases rather than by lengthening the permuted
  list.

- **`empty()` couples the guard to the leaf representation.** Should a leaf ever
  become a truthy value, the guard silently stops distinguishing a leaf from a
  subtree. Mitigation: the coupling is recorded in the decision above, and
  `getRelationsData()` already depends on the same `[]`-is-falsy fact, so the two
  sites fail together and visibly rather than one drifting.

- **Two `Arr` walks per include path instead of one.** Mitigation: not a real
  budget. Include depth is one to three segments and lists are short; the walks
  are over arrays of a handful of keys, once per rendered resource level.

- **Order-independence could be read as permission to stop caring about include
  order anywhere.** It is scoped to tree construction only. Relationship
  resolution still happens in tree-key order, and that order still follows
  first-seen input, it is simply no longer guaranteed.

## Migration Plan

No migration for the package. Consumers upgrade the constraint and, if their
suite fails, declare the recovered relationship in the affected structure rather
than reverting.

Rollback is reverting the four added lines; the change touches no state, no
schema, and no serialized format.

## Open Questions

None. The four scoping decisions (enforcement point, key-order contract, test
surface, release framing) were settled in the propose interview and are recorded
above.

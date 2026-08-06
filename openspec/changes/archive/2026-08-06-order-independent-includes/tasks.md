## 1. Fix the tree builder

- [x] 1.1 In `src/Lodash/Http/Resources/JsonResource.php`, guard the assignment in the private static `undot()` with `if (! empty(Arr::get($result, $item))) { continue; }` so a shorter include path cannot flatten a subtree an earlier longer path built. Use the emptiness test, not `!== []`, which also fires on the `null` returned for an unset path and makes the method return `[]` for every input.
- [x] 1.2 Add a short comment above the guard stating the invariant it enforces: an ancestor path carries no instruction its descendant does not already imply, so the more specific entry wins regardless of order. Do not restate what the code does.
- [x] 1.3 Confirm the characterization table from `design.md` by hand or in a scratch script before writing tests: `['a.b','a']`, `['a','a.b']`, `['a','a.b','a']`, `['a.b.c','a.b']`, `['a.b','a.c']`, `['a','a']`. The first four are the ones that change.

## 2. Extend the test fixture

- [x] 2.1 Add `includeFaculty(): ?JsonResource` to `tests/Unit/Http/Resources/RelationResource.php`, following the existing `includes['<name>'] ?? null` shape, so a three-level `accounts.degree.faculty` chain is expressible.

## 3. Cover the invariant

- [x] 3.1 Add a private permutation helper to `tests/Unit/Http/Resources/JsonResourceRelationsTest.php` that yields every ordering of a given array of include paths.
- [x] 3.2 Add a private helper building a resource populated three levels deep (`accounts` holding `degree` holding `faculty`) plus a top-level `avatar`, so one fixture serves the permutation and depth cases.
- [x] 3.3 Add the permutation test: render `['accounts', 'accounts.degree', 'avatar']` under all six orderings and assert every render equals the first. Use `assertEquals`, whose array comparison is key-order blind; `assertSame` fails on a correct implementation because PHP `===` is key-order sensitive.
- [x] 3.4 Add a test for the two-level ancestor-after-descendant case: `['accounts.degree', 'accounts']` renders the nested resource at `relationships.accounts.data.relationships.degree.data`.
- [x] 3.5 Add a test for the real-world merge shape `['accounts', 'accounts.degree', 'accounts']`, the output of merging always-on defaults with a client include that repeats one of them, asserting the nested relationship survives.
- [x] 3.6 Add a test for the three-level case `['accounts.degree.faculty', 'accounts.degree']`, asserting neither the `degree` nor the `faculty` level is lost.
- [x] 3.7 Add a test that sibling branches still merge: `['accounts.degree', 'accounts.avatar']` renders both beneath `accounts`.
- [x] 3.8 Add a test that a repeated path stays idempotent: `['avatar', 'avatar']` renders `relationships.avatar` exactly as the single-entry case does.
- [x] 3.9 Add a test that an unknown include still throws regardless of position: `['accounts.degree', 'accounts', 'missing']` throws naming `includeMissing`, so preserving the ancestor does not suppress the failure.

## 4. Document the change

- [x] 4.1 Add a `## [15.1.0] - <release date>` entry to `CHANGELOG.md` under `### Fixed`, describing the dropped nested include, naming `array_merge($defaults, $request->getValidatedIncludes())` as the pattern that triggers it, and warning that relationships previously dropped may newly appear. Point suites that newly fail at the `?` nullable marker added in 15.0.0 rather than at reverting.
- [x] 4.2 Add a paragraph to the "API Resources and Relationships" section of `README.md` stating that include paths compose as a set: an ancestor and its descendant may both appear in any order, duplicates are harmless, and merging default includes with client-supplied ones is safe. Keep it to a few sentences alongside the existing populated and empty examples.

## 5. Verify

- [x] 5.1 Run `composer phpcs` and confirm it produces no output.
- [x] 5.2 Run `composer test` and confirm the whole suite passes, including the pre-existing relations cases, which must be untouched by this change.
- [x] 5.3 Confirm the new permutation test fails when the guard from 1.1 is temporarily reverted, so the test is known to bite rather than passing vacuously.

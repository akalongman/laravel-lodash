## Why

An attribute validated with the package's `strict:integer` rule has inert size
bounds. `min`, `max`, `size` and `between` measure a value through Laravel's
`Validator::getSize()`, which compares numerically only when the attribute also
carries one of the framework's own numeric rules (`numeric`, `integer`,
`decimal`). A rule registered through the validator resolver is invisible to that
check, so on a `strict:integer` attribute `max:32767` degrades to a string-length
comparison and admits every out-of-range integer. Measured on this repository's
testbench against Laravel 13: `['strict:integer', 'max:32767']` admits
`2147483648`, `['strict:integer', 'min:1']` admits `0`,
`['strict:integer', 'between:1,10']` admits `999`, and the failures that do
surface use the "characters" message wording. The defect was found as a security
fix in a downstream application, where a database column bound declared in the
validation rules was not being enforced at all.

## What Changes

- **BREAKING** `Validation\Validator` overrides `getSize()` and
  `getAttributeType()` so that an attribute whose `strict` rule names a numeric
  type (`int`, `integer`, `float`, `double`) is measured and messaged as a
  number, without needing Laravel's `integer` or `numeric` rule beside it. Values
  that previously passed inert bounds start failing validation, and the size
  failure message switches from the string wording to the numeric wording.
- `validation-strict` gets its first spec file: a baseline requirement recording
  the rule's native-type gate as it exists today, and a new requirement that size
  rules on strict-numeric attributes compare numerically across `min`, `max`,
  `size`, `between`, `gt`, `gte`, `lt` and `lte`.
- A net-new `tests/Unit/Validation/ValidatorTest.php`. The package has no
  validation tests today. It covers the numeric behavior, the untouched
  non-numeric strict types, the message variant, coexistence with Laravel's own
  `integer` rule, wildcard attributes, the baseline type gate, and pins for the
  four comparison rules the framework already handles on its own.
- `CHANGELOG.md` gains a `16.0.0` entry under `### Changed (BREAKING)`.
- `README.md` gains a "Strict validation rule" subsection under "Extended
  Classes" plus a Table of Contents entry. The rule is undocumented there today.
- `CLAUDE.md` gains a `validation-strict` row in the capability-spec table.

Not in scope: `StrictTypeValidator`, whose type gate is correct as it stands, and
any consumer-side follow-up. One downstream application carries a deliberate
tripwire test asserting the current inert behavior; it is expected to fail when
that application upgrades, and nothing on the package side should prevent that.

## Capabilities

### New Capabilities

- `validation-strict`: already present in the capability map, but with no spec
  file yet. This change writes the first one, so every requirement in it is
  `ADDED`: the existing native-type gate as a baseline, and the numeric
  size-rule behavior this change introduces.

### Modified Capabilities

None.

## Impact

- `src/Lodash/Validation/Validator.php`: two protected overrides and one private
  helper. No public signature changes, no new dependency, no change to
  `StrictTypeValidator` or to the resolver in `ServiceProvider`.
- `tests/Unit/Validation/ValidatorTest.php`: new file.
- `openspec/specs/validation-strict/spec.md`: created at archive time.
- `CHANGELOG.md`, `README.md`, `CLAUDE.md`, and the `16.0.0` release tag.
- Consumers: any `strict:int`, `strict:integer`, `strict:float` or
  `strict:double` attribute that also carries a size rule changes behavior.
  Requests carrying out-of-range numbers that were being admitted are now
  rejected. Consumers that already paired `strict:integer` with `integer` as a
  workaround see no change. Attributes using `strict:string`, `strict:boolean`,
  `strict:array`, or no `strict` rule at all are unaffected.
- The overrides couple to protected framework members (`getSize`,
  `getAttributeType`, `getRule`, `$numericRules`). The new test suite is the pin
  against drift in future Laravel majors.

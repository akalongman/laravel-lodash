## Context

`ServiceProvider` installs `Longman\LaravelLodash\Validation\Validator` through
the validator resolver and registers `strict` on it as an extension backed by
`StrictTypeValidator`. The subclass already overrides one protected member
(`presentOrRuleIsImplicit`), so the hook point for this change exists and is
exercised by every host application that boots the package.

Laravel measures a value for every size rule through one method,
`getSize($attribute, $value)`. It returns the number itself only when the value
is numeric AND `hasRule($attribute, $this->numericRules)` is true; otherwise it
falls through to element count for arrays, kilobytes for files, and character
length for everything else. `getAttributeType($attribute)`, which selects the
`validation.<rule>.<type>` message line, performs the same `hasRule` check.
`$numericRules` defaults to `['Numeric', 'Integer', 'Decimal']`, so a rule added
through `addExtension()` never satisfies it.

Two framework details shape the design. First, `$numericRules` is an instance
property with a per-rule lifecycle: `validateAttribute()` resets it to
`$defaultNumericRules` immediately before invoking each rule method, and the
comparison rules (`validateGt`, `validateGte`, `validateLt`, `validateLte`) each
call `shouldBeNumeric()` first, which appends their own rule name to the list
whenever the value is numeric. That is the framework's own mechanism for
switching a measurement into numeric mode without a numeric rule, and it is why
those four rules are not affected by the defect. Second, `ValidationRuleParser`
studly-cases rule names (`strict:integer` is stored as `['Strict', ['integer']]`)
and explodes wildcard keys into concrete attributes before validation, so
`getRule($attribute, ['Strict'])` sees the parameter for `items.0.qty` exactly as
it does for a top-level attribute.

Measured on this repository's testbench against Laravel 13 before any change,
with the value passed as a PHP integer:

| Rules | Value | Outcome today | Message today |
|---|---|---|---|
| `strict:integer`, `max:32767` | `2147483648` | passes | none |
| `strict:integer`, `min:1` | `0` | passes | none |
| `strict:integer`, `between:1,10` | `999` | passes | none |
| `strict:integer`, `size:5` | `999` | fails | "must be 5 characters" |
| `strict:integer`, `gt:100` | `50` | fails | "must be greater than 100" |
| `strict:integer`, `gte:1` | `0` | fails | "must be greater than or equal to 1" |
| `strict:integer`, `lte:32767` | `2147483648` | fails | "must be less than or equal to 32767" |

The first three rows are the defect. The `size` row fails for the wrong reason
(three characters is not five) and would admit `12345`. The last three rows are
already correct, courtesy of `shouldBeNumeric()`.

`StrictTypeValidator` accepts the aliases `int`, `integer`, `float`, `double`,
`bool`, `boolean`, and otherwise compares against the raw `gettype()` name. There
is no `numeric` alias; `strict:numeric` always fails today because `gettype()`
never returns that string. The numeric type set is therefore exactly `int`,
`integer`, `float`, `double`.

## Goals / Non-Goals

**Goals:**

- Make every size rule on a strict-numeric attribute compare and message as a
  number, with no extra rule required from the consumer.
- Leave non-numeric strict types and attributes without `strict` exactly as the
  framework treats them.
- Reuse the framework's own numeric path rather than reimplementing it, so
  exponent-range handling and any future refinement of that path come for free.
- Pin the behavior of all eight size rules in tests, including the four the
  framework already handles, so the spec's promise survives framework drift.
- Document the rule, which the README does not mention today.

**Non-Goals:**

- Changing `StrictTypeValidator`, including adding a `numeric` alias.
- Changing the `strict` extension's registration in `ServiceProvider`.
- Any consumer-side migration. The downstream tripwire test that asserts the
  inert behavior is meant to fail on upgrade.
- Specifying `presentOrRuleIsImplicit`, the subclass's other override. It is
  unrelated and stays unspecified until a change touches it.

## Decisions

### Append `Strict` to `$numericRules` per measurement and delegate to the parent

```php
protected const array NUMERIC_STRICT_TYPES = ['int', 'integer', 'float', 'double'];

#[Override]
protected function getSize($attribute, $value): int|string
{
    $this->treatStrictNumericTypeAsNumericRule($attribute);

    return parent::getSize($attribute, $value);
}

#[Override]
protected function getAttributeType($attribute): string
{
    $this->treatStrictNumericTypeAsNumericRule($attribute);

    return parent::getAttributeType($attribute);
}

private function treatStrictNumericTypeAsNumericRule(string $attribute): void
{
    if (in_array('Strict', $this->numericRules, true)) {
        return;
    }

    $rule = $this->getRule($attribute, ['Strict']);

    if ($rule === null) {
        return;
    }

    if (in_array($rule[1][0] ?? null, self::NUMERIC_STRICT_TYPES, true)) {
        $this->numericRules[] = 'Strict';
    }
}
```

The attribute genuinely carries a rule named `Strict`, so once `Strict` is in
`$numericRules` the parent's `hasRule($attribute, $this->numericRules)` is true
and its own numeric branch runs, including `ensureExponentWithinAllowedRange()`.
The append happens only after reading the rule's parameter, which is the
distinction that matters: a static `'Strict'` entry in `$numericRules` would be
parameter-blind and classify `strict:string` as numeric, whereas this append is
made per attribute and only when the declared type is numeric.

The mutation is scoped by the framework. `validateAttribute()` resets
`$numericRules` before each rule method runs, so a `Strict` appended while
measuring `max` on one attribute cannot survive into the next rule or the next
attribute. This is the identical lifecycle `shouldBeNumeric()` relies on for the
comparison rules; the change adopts an existing idiom rather than introducing a
new one. The `in_array` guard at the top is not cosmetic: in the field-comparison
form of `gt`/`lt`, the parent calls `getSize()` twice per evaluation with the
same attribute, once per side.

Two conditions from the original brief are deliberately absent. There is no
`is_numeric($value)` test, because the parent applies it after `hasRule` and a
non-numeric value falls through to length as before. There is no "none of
Laravel's numeric rules present" test, because when `integer` sits beside
`strict:integer` the parent is already in numeric mode and the extra entry
changes nothing, which is exactly what the "identical outcomes" scenario asserts.

Considered and rejected:

- **Reimplement the numeric branch inside the override**, returning
  `(string) $this->ensureExponentWithinAllowedRange($attribute, $this->trim($value))`
  under a strict-aware condition. Correct, but it copies one line of framework
  logic and couples the package to two more protected members. If the framework
  refines its numeric path, the copy drifts silently.
- **Add `'Strict'` to `$numericRules` statically**, or to
  `$defaultNumericRules`. Parameter-blind: `strict:string` and `strict:boolean`
  attributes would be sized and messaged as numbers. Rejected in the brief and
  confirmed on reading `getSize()`.
- **Override `validateMin`, `validateMax`, `validateSize` and `validateBetween`**
  to call a `shouldBeNumeric`-style helper before delegating, mirroring how the
  framework wired its comparison rules. Four overrides instead of two, it
  relies on the mutation persisting into message formatting to fix the wording,
  and any size rule the framework adds later would be missed. Hooking the shared
  measurement point covers every present and future size rule at once.

### Keep the `getAttributeType()` override even though the append already fixes messages in practice

A size rule's failure message is formatted inside the same `validateAttribute()`
call that ran `getSize()`, before the next reset, so `Strict` is still in
`$numericRules` when `getSizeMessage()` consults `getAttributeType()`. The
`getSize()` override alone would therefore select the numeric message today. The
second override costs two lines and removes a dependency on that ordering. If a
future framework version formats messages after the reset, or reads the type
before measuring, the message stays correct.

### Message variant follows the declared type, not the runtime value

A strict-numeric attribute that receives a non-numeric string, say `'abc'`
against `['strict:integer', 'max:2']`, fails `max` by length (three characters)
and reports the numeric wording ("must not be greater than 2"). That is a
mismatch between comparison mode and message wording for garbage input.

Accepted, for parity. Vanilla Laravel behaves identically for
`['integer', 'max:2']` with `'abc'`: `getAttributeType()` is keyed on the
declared rules and never looks at the value. The numeric wording is also the
right message for an API consumer, since it states the field's contract, and the
`strict` rule rejects the request with its own message in the same response.
Gating the message on `is_numeric($value)` would make the package stricter than
the framework's own numeric rules for no practical gain.

### The requirement covers all eight size rules; the implementation fixes four; tests pin the other four

The spec promises numeric comparison for `min`, `max`, `size`, `between`, `gt`,
`gte`, `lt` and `lte`. Only the first four are broken; the comparison rules work
because of `shouldBeNumeric()`, a framework internal the package does not
control. The tests for `gt`/`gte`/`lt`/`lte` therefore assert behavior the
package does not implement. That is intentional: the spec promises an outcome,
and if a future framework major drops or reshapes `shouldBeNumeric()`, the pins
fail and the package learns it owes the comparison rules the same treatment.

The alternative, narrowing the requirement to the four broken rules, is more
literal about what the package does but leaves the comparison rules unspecified
and unpinned, so a regression there would be invisible until a consumer found it.

### Test through the public validator surface, in a new file

`tests/Unit/Validation/ValidatorTest.php` extends `Tests\Unit\TestCase`, whose
`getPackageProviders()` registers `ServiceProvider`, so `Validator::make()`
resolves the package subclass with `strict` registered, exactly as in a host
application. Cases drive `passes()`, `errors()` and the rendered message; nothing
reaches into the protected overrides. The file is new because the package has no
validation tests at all today, which is part of how the defect stayed hidden.

Message assertions are limited to the `max` case and the comparison-rule pins.
Every size rule reaches the same `getAttributeType()` path, so asserting wording
on `min`, `between` and `size` as well would re-test one branch four times while
coupling four more tests to translation strings.

Discriminating inputs are chosen so that string mode and numeric mode disagree:
`12345` against `size:5` (five characters, not the number five), `'12'` against
`min:5` on a `strict:string` attribute (two characters, but twelve as a number),
`'12'` against a bare `max:5` (passes only in length mode).

### Release as `16.0.0`

A major. Previously admitted values start failing validation, which is the
definition of breaking for anyone relying on the old behavior even by accident.
The package established the pattern with `15.0.0`: a silent behavior correction
released as a major under `### Changed (BREAKING)` with prose telling consumers
which of their tests will break first. Package majors are decoupled from Laravel
majors (`15.x` targets Laravel 13), so a major costs nothing in alignment.

The changelog heading is `[16.0.0] - 2026-08-27`, following the file's
convention of dated headings; the date is adjusted at tagging if the release
slips.

### Document the rule in the README

The README does not mention `strict` today. A new "Strict validation rule"
subsection under "Extended Classes", beside "Request class" and "API Resources
and Relationships", documents the accepted type aliases, the native-type check
(numeric strings fail `strict:integer`, unlike Laravel's `integer`), and the
size-rule behavior this change adds. A Table of Contents entry follows the
existing pattern.

## Risks / Trade-offs

- **Consumers relying on inert bounds start rejecting requests.** Mitigation:
  the major bump and the `### Changed (BREAKING)` entry, which names the affected
  rule combination and states that downstream tests asserting the old behavior
  are expected to fail. Consumers that already paired `strict:integer` with
  `integer` see no change.

- **Coupling to protected framework members** (`getSize`, `getAttributeType`,
  `getRule`, `$numericRules`, and the per-rule reset lifecycle). Mitigation: the
  append idiom is the framework's own, the parameter read uses the framework's
  own `getRule()` rather than parsing rules by hand, and the test suite pins
  every promised outcome so a change in any of those members surfaces as a
  failing test rather than a silent regression.

- **Message wording and comparison mode can disagree for non-numeric input on a
  strict-numeric attribute.** Accepted for parity with the framework's `integer`
  rule; recorded above and in the spec so it is not mistaken for a bug.

- **`strict:numeric` is a plausible thing to write and silently always fails.**
  Out of scope to add. Mitigation: the README lists the accepted aliases
  explicitly, and the spec states the numeric set is exactly `int`, `integer`,
  `float`, `double`.

- **`Strict` is appended even when `integer` is already present.** Harmless: the
  parent is already in numeric mode, and the "identical outcomes" test proves
  it. Keeping the helper free of a "no framework numeric rule present" check is
  one less condition to reason about.

- **Wildcard attributes rely on rule explosion happening before measurement.**
  Verified in `ValidationRuleParser::explodeWildcardRules()` and covered by a
  dedicated test, so a change in how the framework stores exploded rules is
  caught.

## Migration Plan

No migration for the package. Consumers bump the constraint to `^16.0`, run
their suites, and fix any test that asserted an out-of-range value was accepted
on a strict-numeric attribute; those assertions were asserting the defect.
Consumers who paired `strict:integer` with `integer` as a workaround may drop
the redundant rule at their leisure; keeping it changes nothing.

Rollback is reverting the two overrides and the helper. The change touches no
state, no schema, and no serialized format.

## Open Questions

None. The scoping decisions (mechanism, message semantics, requirement breadth,
test surface, release framing, README inclusion) were settled in the brainstorm,
grilling and propose interview and are recorded above.

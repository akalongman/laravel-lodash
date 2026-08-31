## 1. Write the numeric size-rule tests first, and confirm they bite

- [x] 1.1 Create `tests/Unit/Validation/ValidatorTest.php` in namespace `Tests\Unit\Validation`, extending `Tests\Unit\TestCase`, driving every case through `Validator::make($data, $rules)` and asserting on `passes()` / `fails()` and `errors()`. Pass values as native PHP types (`2147483648`, not `'2147483648'`), since the `strict` rule gates on `gettype()`.
- [x] 1.2 Add the upper-bound case: `['value' => ['strict:integer', 'max:32767']]` rejects `2147483648` and `32768`, accepts `32767`.
- [x] 1.3 Add the lower-bound case: `['strict:integer', 'min:1']` rejects `0` and `-5`, accepts `1`.
- [x] 1.4 Add the range case: `['strict:integer', 'between:1,10']` rejects `999` and `0`, accepts `5`.
- [x] 1.5 Add the exact-size case: `['strict:integer', 'size:5']` rejects `12345` (five characters, not the number five) and accepts `5`.
- [x] 1.6 Add the float case: `['strict:float', 'max:5.5']` rejects `6.0`, accepts `5.5`; repeat one assertion under the `strict:double` alias.
- [x] 1.7 Add the message case: for `['strict:integer', 'max:32767']` with `2147483648`, assert `errors()->first('value')` equals the `validation.max.numeric` line rendered through the app translator for attribute `value` and max `32767`, and does not contain the word `characters`.
- [x] 1.8 Add the coexistence case: `['strict:integer', 'integer', 'max:32767']` produces the same pass/fail outcomes as 1.2 for `2147483648` and `32767`.
- [x] 1.9 Add the wildcard case: `['items.*.qty' => ['strict:integer', 'max:100']]` against `['items' => [['qty' => 5], ['qty' => 500]]]` fails, reports an error for `items.1.qty`, and reports none for `items.0.qty`.
- [x] 1.10 Run `./vendor/bin/phpunit tests/Unit/Validation/ValidatorTest.php` and confirm the new cases fail against the unmodified `Validator`, so the suite is known to bite rather than pass vacuously. Measured: 1.2 through 1.7 and 1.9 fail; 1.8 passes, because Laravel's own `integer` rule already puts the attribute in numeric mode, which is precisely the consumer workaround that case documents. The `size` case fails on both halves, not just the `12345` half, since `mb_strlen('5')` is 1 rather than 5.

## 2. Pin the behavior that must not change

- [x] 2.1 Add the strict-string cases: `['strict:string', 'max:5']` rejects `'999999999'` and `'abcdef'` and accepts `'abc'`; `['strict:string', 'min:5']` rejects `'12'` (two characters, though twelve as a number).
- [x] 2.2 Add the strict-boolean case: `['strict:boolean', 'max:1']` accepts `true`.
- [x] 2.3 Add the no-strict regression guard: a bare `['max:5']` accepts the string `'12'`, which only length mode admits.
- [x] 2.4 Add the comparison-rule pins on `strict:integer`: `gt:100` rejects `50`, `gte:1` rejects `0`, `lt:100` rejects `100`, `lte:32767` rejects `2147483648`, each asserting the corresponding `validation.<rule>.numeric` message line. These pass today because of the framework's `shouldBeNumeric()`; they exist so the spec's promise survives framework drift.
- [x] 2.5 Add the baseline type-gate cases: `strict:integer` accepts `5` and rejects the string `'5'`; `strict:string` accepts `'abc'`; the bare rule `strict` throws `InvalidArgumentException`.
- [x] 2.6 Run the file and confirm every case in this group passes against the unmodified `Validator`, establishing the green baseline before implementation. Measured: 6 tests, 18 assertions, all green before any change to `Validator`.

## 3. Implement the overrides

- [x] 3.1 In `src/Lodash/Validation/Validator.php`, add `protected const array NUMERIC_STRICT_TYPES = ['int', 'integer', 'float', 'double'];`, the `#[Override] protected function getSize($attribute, $value): int|string` and `#[Override] protected function getAttributeType($attribute): string` overrides, and the private helper `treatStrictNumericTypeAsNumericRule(int|string $attribute): void` as laid out in `design.md`: return early if `Strict` is already in `$this->numericRules`, read the rule with `$this->getRule($attribute, ['Strict'])`, return if absent, and append `'Strict'` to `$this->numericRules` only when the first parameter is in `NUMERIC_STRICT_TYPES`. Both overrides call the helper and then delegate to the parent. Import `in_array` with `use function`. Two deviations from `design.md`, both deliberate: the helper's parameter is typed `int|string` rather than `string`, because `Validator::passes()` passes rule-array keys through uncast and PHP turns a numeric attribute name into an integer, which under `declare(strict_types=1)` would raise a TypeError the base validator never raises; and the constant is read through `static::` rather than `self::`, matching the sibling `StrictTypeValidator` and leaving the type list overridable by a subclass. A regression test for the integer-attribute path was added alongside.
- [x] 3.2 Run `./vendor/bin/phpunit tests/Unit/Validation/ValidatorTest.php` and confirm every case in groups 1 and 2 passes. Measured: 15 tests, 40 assertions, all green.

## 4. Document the change

- [x] 4.1 Add a `## [16.0.0] - 2026-08-27` entry at the top of `CHANGELOG.md` under `### Changed (BREAKING)`, in the 15.0.0 house style: state that size rules on `strict:int` / `strict:integer` / `strict:float` / `strict:double` attributes now compare numerically and use the numeric message wording; name the previous behavior (string-length comparison that admitted every out-of-range number); state that consumers already pairing `strict:integer` with `integer` see no change; state that downstream tests asserting the old inert bounds are expected to start failing and should be corrected rather than reverted; note that `strict:string`, `strict:boolean` and attributes without `strict` are unaffected.
- [x] 4.2 Add a `#### Strict validation rule` subsection to `README.md` at the end of the `### Extended Classes` section (immediately before `### Artisan Commands`), and a `- [Strict validation rule](#strict-validation-rule)` line in the Table of Contents directly after the `API Resources and Relationships` entry. Cover: a rules-array usage example; the accepted type aliases (`int`, `integer`, `float`, `double`, `bool`, `boolean`, plus any `gettype()` name such as `string` or `array`); that the check is on the native PHP type so a numeric string fails `strict:integer` unlike Laravel's `integer`; and that on a strict-numeric attribute the size rules (`min`, `max`, `size`, `between`, `gt`, `gte`, `lt`, `lte`) compare numerically with no `integer` rule needed, while `strict:string` keeps character-length semantics. Do not use em or en dashes.
- [x] 4.3 Add a `validation-strict` row to the capability-spec table in `CLAUDE.md`, summarizing the native-type gate and the numeric size-rule behavior in one line, following the existing rows' style.

## 5. Verify

- [x] 5.1 Run `composer phpcs` and confirm it produces no output. Measured: 114 files scanned, no violations reported.
- [x] 5.2 Run `composer test` and confirm the whole suite passes, including every pre-existing test, which this change must not touch. Measured: 157 tests, 932 assertions, all green.

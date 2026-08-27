## ADDED Requirements

### Requirement: The strict rule validates the native PHP type

The `strict:<type>` rule SHALL pass only when the PHP native type of the value
matches the declared type, and SHALL NOT coerce. A numeric string is not an
integer and an integer is not a float. The declared type SHALL be one of the
aliases `int`, `integer`, `float`, `double`, `bool`, `boolean`, or otherwise a
name that `gettype()` returns (`string`, `array`, `NULL`). A `strict` rule with
no type argument SHALL throw an `InvalidArgumentException`.

#### Scenario: Integer accepted, numeric string rejected
- **WHEN** an attribute with the rule `strict:integer` receives the PHP integer `5`
- **THEN** validation passes

#### Scenario: Numeric string rejected by the integer gate
- **WHEN** an attribute with the rule `strict:integer` receives the string `'5'`
- **THEN** validation fails, unlike Laravel's own `integer` rule which admits it

#### Scenario: Native type name accepted as a type argument
- **WHEN** an attribute with the rule `strict:string` receives the string `'abc'`
- **THEN** validation passes

#### Scenario: Missing type argument
- **WHEN** an attribute carries the bare rule `strict`
- **THEN** validation throws an `InvalidArgumentException` naming the missing type argument

### Requirement: Size rules on strict-numeric attributes compare numerically

An attribute whose `strict` rule declares a numeric type (`int`, `integer`,
`float`, `double`) SHALL have every size rule (`min`, `max`, `size`, `between`,
`gt`, `gte`, `lt`, `lte`) compare the value as a number, and SHALL produce the
numeric variant of the size failure message, without needing Laravel's `integer`
or `numeric` rule beside it. Adding Laravel's own `integer` rule next to
`strict:integer` SHALL produce identical outcomes.

An attribute whose `strict` rule declares a non-numeric type (`string`,
`boolean`, `array`, or any other) and an attribute carrying no `strict` rule
SHALL keep the framework's default size semantics: strings by character length,
arrays by element count.

Which message variant is selected follows the declared strict type, not the
runtime value, matching how the framework treats its own `integer` rule. The
numeric comparison itself still applies only to values that are numeric; a
non-numeric value on a strict-numeric attribute is measured by the framework's
default rule for its type while the `strict` rule rejects it.

#### Scenario: Upper bound enforced numerically
- **WHEN** an attribute with the rules `['strict:integer', 'max:32767']` receives `2147483648` or `32768`
- **THEN** validation fails, and the same rules accept `32767`

#### Scenario: Lower bound enforced numerically
- **WHEN** an attribute with the rules `['strict:integer', 'min:1']` receives `0` or a negative integer
- **THEN** validation fails, and the same rules accept `1`

#### Scenario: Range enforced numerically
- **WHEN** an attribute with the rules `['strict:integer', 'between:1,10']` receives `999` or `0`
- **THEN** validation fails, and the same rules accept `5`

#### Scenario: Exact size is the number itself, not its digit count
- **WHEN** an attribute with the rules `['strict:integer', 'size:5']` receives `12345`
- **THEN** validation fails, and the same rules accept `5`

#### Scenario: Float bound enforced numerically
- **WHEN** an attribute with the rules `['strict:float', 'max:5.5']` receives `6.0`
- **THEN** validation fails, and the same rules accept `5.5`

#### Scenario: Comparison rules enforce numerically
- **WHEN** an attribute with the rule `strict:integer` also carries `gt:100`, `gte:1`, `lt:100` or `lte:32767`
- **THEN** `50` fails `gt:100`, `0` fails `gte:1`, `100` fails `lt:100`, and `2147483648` fails `lte:32767`, each with the numeric message variant

#### Scenario: Failure message uses the numeric wording
- **WHEN** an attribute with the rules `['strict:integer', 'max:32767']` receives `2147483648`
- **THEN** the failure message is the `validation.max.numeric` line, not the `validation.max.string` line that mentions characters

#### Scenario: Laravel's own integer rule alongside strict changes nothing
- **WHEN** an attribute with the rules `['strict:integer', 'integer', 'max:32767']` receives `2147483648` and then `32767`
- **THEN** the outcomes are identical to the same values under `['strict:integer', 'max:32767']`

#### Scenario: Strict string attributes still measure by length
- **WHEN** an attribute with the rules `['strict:string', 'max:5']` receives `'999999999'`, or the rules `['strict:string', 'min:5']` receive `'12'`
- **THEN** validation fails in both cases because of the string length, not the numeric value the digits would form

#### Scenario: Strict boolean attributes are untouched
- **WHEN** an attribute with the rules `['strict:boolean', 'max:1']` receives `true`
- **THEN** validation passes, exactly as it did before this requirement

#### Scenario: Attributes without a strict rule keep length semantics
- **WHEN** an attribute with the single rule `max:5` receives the string `'12'`
- **THEN** validation passes, because the two-character string is measured by length

#### Scenario: Wildcard attributes are covered per element
- **WHEN** the rules `['items.*.qty' => ['strict:integer', 'max:100']]` are applied to `['items' => [['qty' => 5], ['qty' => 500]]]`
- **THEN** validation fails on `items.1.qty` and reports no error for `items.0.qty`

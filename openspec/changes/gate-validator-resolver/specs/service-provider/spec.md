## ADDED Requirements

### Requirement: Validator resolver is gated by the validation_rules flag

The main `ServiceProvider` SHALL install the package validator resolver (the
`Longman\LaravelLodash\Validation\Validator` subclass with its `strict`
extension) only when `lodash.register.validation_rules` is truthy. The flag
SHALL default to `true` in `src/config/config.php`. When the flag is falsy, the
provider SHALL leave the host application's validator resolution untouched, so
Laravel's stock validator (or a resolver the host installed) stays in effect.

#### Scenario: Enabled by default
- **WHEN** the package is registered with an unmodified config
- **THEN** `Validator::make()` resolves the package `Validator` subclass and the `strict` rule is available

#### Scenario: Disabled by config
- **WHEN** the host sets `lodash.register.validation_rules` to `false`
- **THEN** `Validator::make()` resolves Laravel's stock validator, ordinary rules keep working, and a declared `strict` rule throws a `BadMethodCallException` because no extension registered it

## 1. Tests first

- [ ] 1.1 Add `it_should_install_the_package_validator_by_default` to `tests/Unit/Validation/ValidatorTest.php`, asserting `Validator::make()` returns an instance of the package `Validator` subclass (passes already; pins the default-on scenario).
- [ ] 1.2 Create `tests/Unit/Validation/ValidatorRegistrationTest.php` extending `Tests\Unit\TestCase`, overriding `defineEnvironment()` to set `lodash.register.validation_rules` to `false`, with two tests: `Validator::make()` does NOT return the package subclass and an ordinary `max` rule still validates; a declared `strict:integer` rule throws `BadMethodCallException`.
- [ ] 1.3 Run the file and confirm the off-state tests fail against the ungated provider (the resolver registers regardless), while 1.1 passes.

## 2. Implement

- [ ] 2.1 In `src/Lodash/ServiceProvider.php`, move the resolver block from `boot()` into `protected function registerValidator(): void` guarded by `if (! config('lodash.register.validation_rules')) { return; }`, called from `boot()` before `loadTranslations()`; place the method alongside the other guarded `registerX()` methods.
- [ ] 2.2 Run the validation test files and confirm everything passes.

## 3. Document

- [ ] 3.1 In the README strict-rule section, change "The service provider registers it automatically" to name the flag: registered when `lodash.register.validation_rules` is `true` (the default), and setting it to `false` leaves Laravel's stock validator in place.
- [ ] 3.2 Add a bullet under the `16.0.0` `### Fixed` changelog section: the `validation_rules` flag now actually gates the validator resolver; consumers who set `false` expecting an opt-out get one, and any `strict` rules they still declare will throw because the extension is no longer registered.

## 4. Verify

- [ ] 4.1 Run `composer phpcs` and confirm no output.
- [ ] 4.2 Run `composer test` and confirm the whole suite passes.

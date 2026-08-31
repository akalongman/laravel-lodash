## Why

The published config ships `lodash.register.validation_rules => true`, but the
`ServiceProvider` registers the validator resolver unconditionally in `boot()`,
so the flag is read nowhere and setting it to `false` does nothing. That
contradicts the `service-provider` spec's standing requirement that the main
provider registers only flag-gated behavior, and it strands a consumer who
needs their own validator resolver: the package silently overwrites it. Found
by the code review of the `strict-numeric-size-rules` release.

## What Changes

- `ServiceProvider::boot()` moves the resolver registration into a
  `registerValidator()` method guarded by `config('lodash.register.validation_rules')`,
  matching the existing `registerCommands()` / `registerRequestMacros()` /
  `loadTranslations()` idiom.
- Setting the flag to `false` becomes a real opt-out: the package `Validator`
  (the `strict` rule, the strict-numeric size behavior, and the
  `presentOrRuleIsImplicit` override) is not installed, and Laravel's stock
  validator remains in place. The default stays `true`, so consumers who have
  not touched the flag see no change.
- The `service-provider` spec gains an explicit requirement for the gate.
- Tests cover both positions of the flag; the README's "registers it
  automatically" sentence gains the flag reference; `CHANGELOG.md` documents
  the newly effective opt-out under the unreleased `16.0.0` entry.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `service-provider`: adds a requirement that the validator resolver is gated
  by `lodash.register.validation_rules`. The existing "Minimal main provider
  with opt-in flags" requirement already demands this generically; the new
  requirement pins the specific flag and the observable off-state behavior.

## Impact

- `src/Lodash/ServiceProvider.php`: extract and guard the resolver block.
- `tests/Unit/Validation/ValidatorRegistrationTest.php`: new file covering the
  off state; `ValidatorTest.php` gains a default-on pin.
- `openspec/specs/service-provider/spec.md`: one added requirement at archive.
- `README.md`, `CHANGELOG.md`.
- Consumers who set `validation_rules => false` in an already-published config
  (expecting an opt-out that never worked) get what they asked for on upgrade:
  any `strict` rules they still declare will then throw
  `BadMethodCallException`, since the extension is gone. This is the flag
  honoring its published contract and is called out in the changelog.

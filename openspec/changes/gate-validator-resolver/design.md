## Context

`ServiceProvider::boot()` registers the validator resolver inline and
unconditionally, while every other behavior the provider wires (commands,
request macros, translations) sits in a guarded `registerX()` method reading a
`lodash.register.*` flag. The `validation_rules` flag has shipped in
`src/config/config.php` since the config's introduction and is read nowhere;
the `service-provider` spec's "Minimal main provider with opt-in flags"
requirement demands the gate generically.

## Goals / Non-Goals

**Goals:**

- Make `lodash.register.validation_rules` a working opt-out, defaulting on.
- Match the provider's existing guarded-method idiom exactly.

**Non-Goals:**

- Any change to the `Validator` subclass, `StrictTypeValidator`, or the
  resolver closure's contents.
- Splitting the flag (separate switches for the `strict` rule versus the
  `presentOrRuleIsImplicit` behavior). One flag governs the one resolver, as
  the published config always implied.

## Decisions

### Guard in a registerValidator() method, all-or-nothing

Extract the resolver block from `boot()` into
`protected function registerValidator(): void` opening with
`if (! config('lodash.register.validation_rules')) { return; }`, the literal
shape of `registerCommands()`. Disabling removes the entire custom validator,
including `presentOrRuleIsImplicit`: the resolver is one unit, and a consumer
opting out is asking for stock Laravel resolution, most likely to install their
own resolver. Splitting flags would mint config surface for a persona nobody
has named.

### The off-state contract is "stock Laravel", not "strict quietly passes"

With the flag off, a still-declared `strict` rule throws
`BadMethodCallException` from Laravel's `__call`. That loud failure is correct:
silently skipping a declared rule would reintroduce exactly the inert-rule
defect class the 16.0.0 release exists to close.

## Risks / Trade-offs

- **Consumers who set `false` long ago and kept using `strict`** start
  throwing on upgrade. That combination was always contradictory; the
  changelog names it. The default-on posture protects everyone else.
- **The test for the off state depends on provider boot order in testbench.**
  Mitigated by driving it through the real `Tests\Unit\TestCase` bootstrap
  with only a config override, no manual provider juggling.

## Migration Plan

Nothing for consumers using defaults. A consumer who wants the package
validator gone sets the flag to `false` and removes any `strict` rules.
Rollback is reverting the guard.

## Open Questions

None. Wiring versus removing versus documenting the flag was put to the
maintainer explicitly; wiring won, with this cycle mandated to run end to end.

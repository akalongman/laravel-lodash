# The validator opt-out switch works

## Problem

The package ships a configuration switch that promises applications a way to
turn off its custom validation layer. The switch has never been connected to
anything: whatever an application sets it to, the package installs its
validator anyway, silently replacing one the application may have wanted to
provide itself. A recent code review of the validation release found the
disconnect.

## Solution

The switch now does what its name says. Left alone (the default), nothing
changes for anyone. Switched off, the package keeps its hands off validation
entirely and the application gets Laravel's standard behavior, or its own.

## Business Outcome

The published configuration stops lying. Applications that need to opt out of
the package's validation, for example to run their own customized validator,
can now actually do so, and the package's own rulebook (every feature is
opt-in by a switch) holds for validation the way it already holds for
commands, request helpers, and translations.

## Risks & Timing

Small effort, shipping inside the already-open 16.0.0 release. The one edge:
an application that switched the flag off long ago (to no effect) while still
using the package's `strict` rule will now see those rules error loudly,
because the rule is gone once the switch is honored. That combination was
always contradictory, and the release notes call it out.

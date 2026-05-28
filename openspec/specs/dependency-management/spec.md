# dependency-management Specification

## Purpose

The `dependency-management` capability captures the Composer constraint discipline applied to `longman/laravel-lodash`. It defines the caret-range policy, the split between `require`, `require-dev`, and `suggest`, the stuck-dev-dep pin-and-follow-up rule that prevents a single recalcitrant tool from blocking a Laravel upgrade, the requirement that any bump to `composer.json#require.laravel/framework` or `require.php` is accompanied by matching CI and README updates in the same change, and the rule that `composer.lock` is committed and kept current.

## Requirements

### Requirement: Constraint floors use caret ranges

Every dependency in `composer.json#require` and `composer.json#require-dev` SHALL declare its constraint using a caret range (`^X.Y` or `^X.Y.Z`). The package MUST NOT pin to exact versions or use tilde ranges (`~X.Y`) except when a specific upstream bug forces it, in which case the constraint SHALL carry an inline rationale captured in the `dependency-management` capability's notes (e.g., `tasks.md` follow-up or `CHANGELOG.md` entry).

#### Scenario: Adding a new dependency

- **WHEN** a contributor adds a new entry to `composer.json#require` or `require-dev`
- **THEN** the constraint SHALL use a caret range expressing the minimum supported major.minor

#### Scenario: An exception to the caret rule

- **WHEN** an upstream regression forces pinning to an exact version
- **THEN** the pin SHALL be documented as a follow-up in `tasks.md` or a tracking note
- **AND** the constraint SHALL be relaxed back to a caret range as soon as the upstream regression is resolved

### Requirement: Runtime vs. dev dependency placement

Dependencies that are loaded at runtime (autoload imports, service-provider registrations, helper functions) SHALL be declared in `composer.json#require`. Dependencies used only for testing, linting, or local diagnostics SHALL be declared in `composer.json#require-dev`. A package SHALL NOT appear in both.

#### Scenario: A package is autoloaded by the package's runtime code

- **WHEN** a class in `src/Lodash/` imports a vendor class
- **THEN** the vendor's package SHALL appear in `composer.json#require`

#### Scenario: A package is referenced only from `tests/`

- **WHEN** the only usages of a vendor are under `tests/`
- **THEN** the vendor's package SHALL appear in `composer.json#require-dev`

### Requirement: Optional runtime extras live in `suggest`

Features that the host application opts into (e.g., Elasticsearch, AWS SQS, igbinary serializer) SHALL NOT be declared in `composer.json#require`. They MUST be declared in `composer.json#suggest` with a one-line explanation of when the consumer should install them. The package's runtime code SHALL check class/extension existence before using an optional dependency, so missing suggests fail with an actionable error rather than a fatal autoload error.

#### Scenario: Consumer installs without an optional dependency

- **WHEN** a consumer installs `longman/laravel-lodash` without one of the `suggest`-ed packages
- **THEN** the install SHALL succeed
- **AND** the feature that needs the missing package SHALL surface a clear error (e.g., a `class_exists()` guard with an exception, not a PHP fatal) only when the consumer attempts to use it

#### Scenario: A new optional feature is added

- **WHEN** a contributor adds a feature that depends on an external package
- **THEN** the contributor SHALL evaluate whether the dependency belongs in `require` (always-needed) or `suggest` (opt-in)
- **AND** if `suggest`, the runtime code SHALL guard against the dependency's absence

### Requirement: Stuck dev-dep policy

If a `require-dev` entry has no release compatible with the currently targeted Laravel major, the package SHALL pin that dev-dep to the highest version it can resolve against the targeted Laravel and add a follow-up entry to the change's `tasks.md` (or, if applied, a tracking issue) noting the pin. A stuck dev-dep MUST NOT block a Laravel upgrade. A stuck dev-dep that exists only for diagnostics and has no consumers in `tests/` MAY be removed instead of pinned.

#### Scenario: A dev-dep has no Laravel-N-compatible release

- **WHEN** the package is being upgraded to Laravel N and a `require-dev` entry has no Laravel-N-compatible release
- **THEN** the dev-dep SHALL be pinned to the highest resolvable version
- **AND** the change's `tasks.md` SHALL include an explicit follow-up entry naming the dep and the condition to revisit

#### Scenario: A diagnostics-only dev-dep is stuck

- **WHEN** a stuck dev-dep is used only for local-developer diagnostics (no usages in `tests/` or `src/`)
- **THEN** the contributor MAY drop the dev-dep from `composer.json#require-dev` entirely
- **AND** the drop SHALL be documented in `CHANGELOG.md`

### Requirement: Laravel and PHP floors are bumped together with their CI counterparts

When `composer.json#require.laravel/framework` or `composer.json#require.php` is changed, the `.github/workflows/php.yml` PHP version SHALL be updated in the same change, and any README compatibility table SHALL be updated in the same change.

#### Scenario: Bumping the Laravel floor

- **WHEN** a contributor edits `composer.json#require.laravel/framework`
- **THEN** the same diff SHALL update `.github/workflows/php.yml`'s `php-version` (if affected) and the README compatibility section
- **AND** CI SHALL fail in the absence of any of these companion updates that affect testability

#### Scenario: Bumping the PHP floor

- **WHEN** a contributor edits `composer.json#require.php`
- **THEN** the same diff SHALL update `.github/workflows/php.yml`'s `php-version`
- **AND** the README install section SHALL be updated to reflect the new PHP minimum

### Requirement: `composer.lock` is committed and current

The repository SHALL commit `composer.lock`. Every change that edits `composer.json` SHALL also commit the regenerated `composer.lock`. CI's `composer validate` step SHALL fail when `composer.lock` is stale relative to `composer.json`.

#### Scenario: A change edits composer.json but forgets composer.lock

- **WHEN** a PR diff edits `composer.json` without regenerating `composer.lock`
- **THEN** CI's `composer validate` step SHALL fail
- **AND** the contributor SHALL run `composer update --lock` (or a targeted `composer require`/`update`) and commit the result

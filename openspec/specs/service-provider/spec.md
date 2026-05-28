# service-provider Specification

## Purpose

The `service-provider` capability defines how `longman/laravel-lodash`'s main Laravel service provider bootstraps the package and how feature providers extend Laravel's own providers. It captures the package's Laravel and PHP floors, the rule that the main provider stays minimal (every feature it registers is gated by a `lodash.register.*` config flag), and the contract that subclassed feature providers (`Cache`, `Redis`, `Queue`, `Elasticsearch`, `Debug`) must remain signature-compatible with the Laravel parents they extend.

## Requirements

### Requirement: Laravel framework floor

The package SHALL declare `laravel/framework: ^13.0` in `composer.json#require` and MUST NOT support older Laravel majors in the same release line. Consumers on a prior Laravel major MUST stay on a prior `^N`-compatible tag of laravel-lodash, where N matches their framework major.

#### Scenario: Composer require declares Laravel 13

- **WHEN** a developer reads `composer.json#require`
- **THEN** `laravel/framework` SHALL be constrained to `^13.0` exactly
- **AND** no `^12.0 || ^13.0` style dual-major constraint SHALL appear

#### Scenario: Installing into a Laravel 12 host fails fast

- **WHEN** a host application with `laravel/framework ^12.0` runs `composer require longman/laravel-lodash`
- **THEN** composer SHALL refuse the install with a constraint-mismatch error
- **AND** the README SHALL document that the consumer must either upgrade their host to Laravel 13 or pin laravel-lodash to a prior tag

### Requirement: PHP floor

The package SHALL declare `php: ^8.4` in `composer.json#require`. The floor MUST NOT be raised beyond `^8.4` in this change, even if Laravel 13 itself transitively requires a higher PHP version.

#### Scenario: Composer require declares PHP 8.4

- **WHEN** a developer reads `composer.json#require`
- **THEN** the `php` constraint SHALL be `^8.4`

#### Scenario: Transitive PHP requirement is left to composer resolution

- **WHEN** Laravel 13 requires a higher PHP version than `^8.4`
- **THEN** composer SHALL surface the conflict at install time via the transitive constraint
- **AND** the lodash `composer.json#require.php` SHALL NOT be modified solely to mirror the transitive bound

### Requirement: Minimal main provider with opt-in flags

The main `Longman\LaravelLodash\ServiceProvider` SHALL register only behavior that is gated by a `lodash.register.*` config flag, except for the package config merge/publish step which is unconditional. Feature providers (`Cache`, `Redis`, `Queue`, `Elasticsearch`, `Debug`) MUST NOT be auto-registered by the main provider; they SHALL be wired in by the host application via `config/app.php`.

#### Scenario: Adding a new behavior to the main provider

- **WHEN** a contributor adds a new piece of behavior to the main `ServiceProvider`
- **THEN** the behavior SHALL be gated by a `lodash.register.<flag>` config key
- **AND** the default value of `<flag>` in `src/config/config.php` SHALL preserve the package's existing opt-in posture
- **AND** the README SHALL document the new flag

#### Scenario: A feature provider is required for opt-in behavior

- **WHEN** a feature (extended Cache, Redis, Queue, Elasticsearch, Debug) needs to be activated
- **THEN** the host application SHALL add the corresponding `Longman\LaravelLodash\<Feature>\<Feature>ServiceProvider` to its `config/app.php`
- **AND** the main `Longman\LaravelLodash\ServiceProvider` SHALL NOT register that provider automatically

### Requirement: Extended providers preserve parent contracts

Feature providers and managers that extend Laravel parents (`CacheManager`, `RedisManager`, `QueueServiceProvider`, etc.) SHALL keep their public and protected method signatures compatible with the parent's signatures in the targeted Laravel major.

#### Scenario: Laravel parent signature changes between majors

- **WHEN** Laravel 13 changes a method signature on a parent provider or manager that lodash extends
- **THEN** the corresponding lodash subclass SHALL update its signature to match
- **AND** the change SHALL be documented in `CHANGELOG.md` if it is observable to consumers (e.g., a new constructor parameter exposed publicly)

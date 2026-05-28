# Changelog

All notable changes to `longman/laravel-lodash` are documented in this file.

## [Unreleased]

### Changed (BREAKING)

- Require `laravel/framework ^13.0`. Drops support for Laravel 12. Consumers on Laravel 12 stay on the `^13.x` tag of this package.
- Migrate Elasticsearch SDK imports from `Elasticsearch\Client` / `Elasticsearch\ClientBuilder` to `Elastic\Elasticsearch\Client` / `Elastic\Elasticsearch\ClientBuilder` (vendor namespace change in `elasticsearch/elasticsearch ^9.0`). `ElasticsearchManager` now calls `->asArray()` / `->asBool()` on SDK responses so downstream array access keeps working.
- Lift dev-deps to the latest Laravel 13-compatible majors: `orchestra/testbench ^11.0`, `phpunit/phpunit ^13.0`, `laravel/horizon ^5.47`, `laravel/passport ^13.0`, `elasticsearch/elasticsearch ^9.0`. `laravel/tinker` follows as a transitive bump to `^3.0`.

### Fixed

- Replace 4 calls to the long-removed `str_random()` global helper with `Illuminate\Support\Str::random()` in `SqsFifoQueue`, `UserAdd`, and `UserPassword`. The previous code would have errored at runtime under any supported Laravel.

### Tooling

- Refresh `.github/workflows/php.yml`: `actions/checkout@v4`, `actions/cache@v4`, explicit PHP setup via `shivammathur/setup-php@v2` (PHP 8.4, extensions `mbstring intl redis igbinary json`), drop the deprecated `--no-suggest` flag.
- Migrate `phpunit.xml.dist` to the PHPUnit 13.1 schema (via `phpunit --migrate-configuration`).

### Documentation

- README installation section now states `laravel/framework ^13.0` and `php ^8.4` as the supported floors, and adds a compatibility table mapping `longman/laravel-lodash` majors to Laravel majors.
- `CLAUDE.md` adds a "Capability specs (Progressive Disclosure)" table referencing the three new specs (`service-provider`, `ci-pipeline`, `dependency-management`) under `openspec/specs/`.

### Known issues / follow-ups

- `Longman\LaravelLodash\Auth\Passport\PassportServiceProvider::makeGuard()` declares return type `RequestGuard`, which is LSP-incompatible with Passport's parent return type `TokenGuard` and fatals on class autoload. This is a pre-existing bug (Passport 12 had the same parent signature), surfaced by the L13 audit. Removing it requires deleting the override, the `RequestGuard` class, and three references in `AuthService` — out of scope for this upgrade. Tracked as a follow-up.

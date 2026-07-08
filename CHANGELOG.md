# Changelog

All notable changes to `longman/laravel-lodash` are documented in this file.

## [Unreleased]

### Fixed

- Refresh `composer.lock` to the latest resolvable versions (`laravel/framework` 13.19.0, `aws/aws-sdk-php` 3.388.0, `phpunit/phpunit` 13.2.4, `guzzlehttp/guzzle` 7.13.2, and transitive updates). Resolves four Dependabot alerts: guzzle dot-only cookie-domain matching and silent HTTPS-proxy downgrade (GHSA-cwxw-98qj-8qjx, GHSA-wpwq-4j6v-78m3), psr7 CRLF injection (GHSA-vm85-hxw5-5432), and phpseclib SSRF via X.509 AIA (GHSA-m557-wrgg-6rp4). No `composer.json` constraint changes; no direct dependency has a newer major available.

## [14.0.0] - 2026-07-08

### Changed (BREAKING)

- Require `laravel/framework ^13.0`. Drops support for Laravel 12. Consumers on Laravel 12 stay on the `^13.x` tag of this package.
- `Testing\Response::assertJsonDataCollectionStructure()` now validates every element of `data` via the framework `*` wildcard. Previously only the first element was checked, so heterogeneous rows passed silently; suites that were hiding such rows will newly fail, which is the assertion doing its job.
- `Testing\DataStructuresProvider` relation includes get a defined grammar: segments are `name` or `name[]` (collection), each optionally `:StructureName`, composed with dots for linear chains (`'roles[].admins[].item'`) and nested arrays for branching (`'program:AdminProgram' => ['faculty:AdminFaculty']`), merging order-independently. Collection relations are emitted under `['data']['*']` instead of `['data'][0]`, so nested collections validate every row and compose with exact mode. The legacy `'[roles]'` wrapper form and malformed segments now throw `InvalidArgumentException` with a migration hint (rewrite `'[roles]'` as `'roles[]'`; plain dotted paths remain valid). The protected `includeNestedRelations()` / `includeNestedRelation()` methods are removed.
- Migrate Elasticsearch SDK imports from `Elasticsearch\Client` / `Elasticsearch\ClientBuilder` to `Elastic\Elasticsearch\Client` / `Elastic\Elasticsearch\ClientBuilder` (vendor namespace change in `elasticsearch/elasticsearch ^9.0`). `ElasticsearchManager` now calls `->asArray()` / `->asBool()` on SDK responses so downstream array access keeps working.
- Lift dev-deps to the latest Laravel 13-compatible majors: `orchestra/testbench ^11.0`, `phpunit/phpunit ^13.0`, `laravel/horizon ^5.47`, `laravel/passport ^13.0`, `elasticsearch/elasticsearch ^9.0`. `laravel/tinker` follows as a transitive bump to `^3.0`.

### Added

- `bool $exact = false` parameter on `Testing\Response::assertJsonDataItemStructure()` and `assertJsonDataCollectionStructure()`: exact key-set matching scoped to the `data` subtree and, when pager or cursor meta is included, to `meta.pagination`; envelope keys outside those subtrees stay loosely checked. Subclasses overriding these two methods must update their signatures for the new optional parameter.
- Guard chain with readable failure messages in `assertJsonDataCollectionStructure()`: a missing `data` key, an empty collection, and a non-list `data` now fail with explicit assertion messages instead of PHP warnings or deep framework errors.
- `Testing\Response::setDataStructuresProvider()` and combined resource assertions `assertJsonDataResource()` / `assertJsonDataResources()` (exact by default): resolve a named `DataStructuresProvider` structure, assert the wire `type` (defaults to the structure name, overridable via `type:`), and assert model identity: `data.id` for items, the id set (or sequence with `ordered: true`) for collections, which also asserts the row count. An empty model set asserts that `data` is exactly `[]`, serving "sees nothing" scoping proofs.

### Fixed

- Replace 4 calls to the long-removed `str_random()` global helper with `Illuminate\Support\Str::random()` in `SqsFifoQueue`, `UserAdd`, and `UserPassword`. The previous code would have errored at runtime under any supported Laravel.
- Replace `is_callable(['static', ...])` in `Testing\DataStructuresProvider` structure resolution with explicit method and property checks, removing a "Use of static in callables" deprecation notice emitted on PHP 8.4.

### Tooling

- Refresh `.github/workflows/php.yml`: `actions/checkout@v4`, `actions/cache@v4`, explicit PHP setup via `shivammathur/setup-php@v2` (PHP 8.4, extensions `mbstring intl redis igbinary json`), drop the deprecated `--no-suggest` flag.
- Migrate `phpunit.xml.dist` to the PHPUnit 13.1 schema (via `phpunit --migrate-configuration`).

### Documentation

- README installation section now states `laravel/framework ^13.0` and `php ^8.4` as the supported floors, and adds a compatibility table mapping `longman/laravel-lodash` majors to Laravel majors.
- `CLAUDE.md` adds a "Capability specs (Progressive Disclosure)" table referencing the three new specs (`service-provider`, `ci-pipeline`, `dependency-management`) under `openspec/specs/`.

### Known issues / follow-ups

- `Longman\LaravelLodash\Auth\Passport\PassportServiceProvider::makeGuard()` declares return type `RequestGuard`, which is LSP-incompatible with Passport's parent return type `TokenGuard` and fatals on class autoload. This is a pre-existing bug (Passport 12 had the same parent signature), surfaced by the L13 audit. Removing it requires deleting the override, the `RequestGuard` class, and three references in `AuthService` — out of scope for this upgrade. Tracked as a follow-up.

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project type

This is a **Laravel package** (not an application), published as `longman/laravel-lodash` on Packagist. It adds utility functionality to Laravel applications. Requires `php ^8.4` and `laravel/framework ^13.0`. Tests run against a bootstrapped Laravel instance via `orchestra/testbench`.

## Common commands

```bash
composer test          # Run PHPUnit test suite (phpunit.xml.dist)
composer phpcs         # Lint using custom longman/php-code-style ruleset
composer phpcbf        # Auto-fix code style issues
composer coverage-html # Generate HTML coverage report to build/coverage
```

Run a single test file or method:
```bash
./vendor/bin/phpunit tests/Unit/Eloquent/SomeTest.php
./vendor/bin/phpunit --filter testMethodName tests/Unit/Eloquent/SomeTest.php
```

Before opening a PR: `composer phpcs` must produce no output, and `composer test` must pass. See `CONTRIBUTING.md`.

## Architecture

### Autoload layout
- Production PSR-4: `Longman\LaravelLodash\` → `src/Lodash/`
- Global helpers are autoloaded from `src/helpers.php` (composer `files` autoload).
- Package config source: `src/config/config.php` (published to `config/lodash.php`).
- Tests PSR-4: `Tests\` → `tests/`, bootstrapped via `tests/Bootstrap.php`.

### Service provider model
`src/Lodash/ServiceProvider.php` is the package's main entry point (registered via `extra.laravel.providers` in `composer.json`, so Laravel auto-discovers it). It:
1. Merges/publishes `lodash.php` config.
2. Registers artisan commands, but only if `lodash.register.commands` is truthy.
3. Registers Request macros (`getInt`, `getBool`, `getFloat`, `getString`) conditional on `lodash.register.request_macros`.
4. Installs a custom `Validator` resolver adding a `strict` rule backed by `StrictTypeValidator`.
5. Loads translations gated by `lodash.register.translations`.

**Important:** most feature-specific providers (`Cache`, `Redis`, `Queue`, `Elasticsearch`, `Debug`) are **not** registered by the main `ServiceProvider`. They're opt-in. The README explicitly tells users to add them to `config/app.php` and sometimes to remove Laravel's built-in equivalents, because Lodash's providers *extend* Laravel's (e.g. `Longman\LaravelLodash\Redis\RedisServiceProvider` subclasses Laravel's Redis provider to add igbinary serializer and client-side sharding support).

This pattern matters when editing: changes to, say, the Queue provider only affect users who opted in by replacing Laravel's `QueueServiceProvider`.

### Subsystem directories under `src/Lodash/`
Each directory corresponds to a Laravel subsystem this package extends:
- `Auth` — simple basic auth.
- `Cache`, `Redis` — extended providers adding igbinary / sharding support.
- `Commands` — artisan commands (`clear-all`, `db:clear`, `db:dump`, `db:restore`, `log:clear`, `user:add`, `user:password`).
- `Composer` — `ComposerChecker` / scripts to verify `vendor/` matches `composer.lock` at runtime.
- `Debug` — IP-gated debug mode.
- `Elasticsearch` — thin wrapper around the official SDK behind `ElasticsearchManagerContract`.
- `Eloquent` — traits: `UserIdentities` (created_by/updated_by/deleted_by), `UsesUuidAsPrimary`, `ManyToManyPreload` (with `limitPerGroupViaUnion` and `limitPerGroupViaSubQuery`), plus `Casts/`.
- `Foundation`, `Support` — helper classes (`Arr`, `Str`, `Uuid`, `Declensions`).
- `Http` — extended `Request` class, custom form `Requests`, and `Resources` including `ResourceResponse` + `PaginatedResourceResponse` (supports cursor pagination).
- `Log` — logging extensions.
- `Middlewares` — `XssSecurity`, `SimpleBasicAuth`, etc.
- `Queue` — SQS FIFO driver support.
- `Testing` — test helpers: `Concerns/InteractsWithDatabase`, `Constraints/`, `DataStructuresProvider`, `FakeDataProvider`, `Response`.
- `Validation` — custom validator with `strict` rule.

### Tests
- Live under `tests/Unit/`, grouped by subsystem (`Eloquent/`, `Http/`, `Middleware/`, `Support/`, `Testing/`).
- Common base class: `tests/Unit/TestCase.php`.
- `phpunit.xml.dist` sets `APP_ENV=testing`, `CACHE_DRIVER=array`, `SESSION_DRIVER=array`, `QUEUE_DRIVER=sync`. Coverage includes `src/` but excludes `src/config/`.

## Code style

The project uses `longman/php-code-style` (a custom phpcs ruleset stricter than PSR-12). Notable enforced rules from `phpcs.xml`: the standard rules apply to both `src/` and `tests/`, except `PSR1.Methods.CamelCapsMethodName.NotCamelCaps` is excluded for tests (so tests can use snake_case method names).

Additional conventions already used throughout the codebase:
- `declare(strict_types=1);` at the top of every PHP file.
- Typed properties (no docblock types) and explicit return types including `void`.
- Functions from PHP stdlib are imported with `use function ...;` at the top (see `ServiceProvider.php`).
- Short nullable syntax (`?Type`), not `Type|null`.

Follow the rules in `~/.claude/rules/php.md` for all PHP/Laravel work in this repo.

## Capability specs (Progressive Disclosure)

For canonical detail, read the cited capability spec under `openspec/specs/<name>/spec.md`. Rules below are summaries; the spec is the source of truth.

| Topic | Spec | Rule summary |
|---|---|---|
| Package bootstrap and composer manifest contract | `service-provider` | `composer.json#require` SHALL declare `laravel/framework: ^13.0` and `php: ^8.4`. Main provider registers behavior only when gated by `lodash.register.*` flags; feature providers (Cache, Redis, Queue, Elasticsearch, Debug) MUST be opt-in via host `config/app.php`. Extended providers preserve parent signatures. |
| GitHub Actions CI workflow | `ci-pipeline` | `.github/workflows/php.yml` runs on push and PR to `master`, single job on `ubuntu-latest` (no PHP / Laravel matrix). Third-party Actions pinned to a major whose JavaScript runtime is still supported by the runner (currently `actions/checkout@v6`, `actions/cache@v5`, both on Node 24). PHP set up explicitly via `shivammathur/setup-php@v2` matching `composer.json#require.php`. Step order: `validate` → cached `install` → `phpcs` → `test`. `--no-suggest` flag forbidden. |
| Composer dependency discipline | `dependency-management` | Every constraint uses a caret range; runtime deps in `require`, dev-only in `require-dev`, optional extras in `suggest` guarded at runtime. Stuck dev-deps without a Laravel-N-compatible release are pinned at the highest resolvable version with a documented follow-up; diagnostics-only stuck deps MAY be dropped. `composer.json` bumps SHALL bump `composer.lock` and CI PHP version in the same change. |
| Testing assertions and data structures | `testing-utilities` | `Testing\Response` collection assertions validate every `data` row (`*` wildcard) behind a guard chain; `exact:` mode exact-matches the `data` and `meta.pagination` subtrees only. Resource assertions (`assertJsonDataResource`/`assertJsonDataResources`, exact by default) also assert wire `type` and model id set/sequence. `DataStructuresProvider` includes use dots or nested arrays with `name[]` collection markers, merged order-independently; the legacy `'[roles]'` wrapper throws. Internal helpers MUST NOT match the `get*Structure` pattern (PHP method names are case-insensitive). |

## Things to know before editing

- **Do not add features to `ServiceProvider` that aren't gated by a `lodash.register.*` config flag.** The provider is intentionally minimal so users can disable parts.
- When touching `Cache/`, `Redis/`, `Queue/`, or `Elasticsearch/` providers, remember that they are drop-in replacements for Laravel's own. Signature compatibility with the parent Laravel provider matters.
- Migrations: the user's global PHP guidelines say migrations should only have `up()` methods (no `down()`). This package doesn't ship migrations, but keep this in mind if adding any.
- `tests/Bootstrap.php` is minimal; it does not boot a Laravel app. Test cases that need the container must extend `Orchestra\Testbench\TestCase` (see `tests/Unit/TestCase.php`).

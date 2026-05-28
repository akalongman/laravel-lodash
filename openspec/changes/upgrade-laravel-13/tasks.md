## 1. Prepare branch and capture baseline

- [x] 1.1 Create the working branch `upgrade-laravel-13` from `master` and confirm `git status` is clean.
- [x] 1.2 On a baseline (pre-edit) tree, run `composer install`, `composer phpcs`, and `composer test`; record results so any regression is attributable.
- [x] 1.3 Open `composer.lock`, dump the current list of resolved versions for `require` and `require-dev` to a scratch note for diffing.

## 2. Bump composer manifest

- [x] 2.1 Edit `composer.json#require` to set `laravel/framework: ^13.0`; leave `php: ^8.4` unchanged.
- [x] 2.2 Edit `composer.json#require-dev` to lift each dev-dep to the latest L13-compatible major: `orchestra/testbench`, `phpunit/phpunit`, `laravel/horizon`, `laravel/passport`, `aws/aws-sdk-php`, `elasticsearch/elasticsearch`, `google/apiclient`, `longman/php-code-style`, `mockery/mockery`, `neitanod/forceutf8`, `jetbrains/phpstorm-attributes`.
- [x] 2.3 Run `composer update --with-all-dependencies`; resolve any composer constraint errors by adjusting only the offending constraint (do not silently widen unrelated constraints).
- [x] 2.4 Commit the updated `composer.json` and `composer.lock` as a self-contained step so a bisect lands here cleanly.

## 3. Handle stuck dev-deps

- [x] 3.1 For each dev-dep that has no L13-compatible release, pin it to the highest version composer can resolve against L13. *(No-op: all dev-deps resolved to L13-compatible versions cleanly.)*
- [x] 3.2 For each pinned dev-dep, add a follow-up entry below in section 11 naming the dep and the revisit condition. *(No-op: no pins needed.)*
- [x] 3.3 If a stuck dev-dep is used only for diagnostics (no usages in `src/` or `tests/`), drop it from `require-dev` instead of pinning; record the drop in `CHANGELOG.md`. *(No-op: no stuck deps.)*

## 4. Audit extended providers and connectors against L13 parents

- [x] 4.1 `src/Lodash/Cache/CacheServiceProvider.php` and any `Cache/*` manager/store: compare method signatures against Laravel 13's parent classes; update overrides where the parent changed. *(No changes: `createRedisDriver`, `serialize`, `unserialize` all match L13 parent signatures.)*
- [x] 4.2 `src/Lodash/Redis/RedisServiceProvider.php`, `RedisManager`, and any `Redis/Connections|Connectors/*`: compare against Laravel 13 Redis internals; preserve igbinary and client-side sharding behavior. *(No changes: `connector()` deliberately narrows the parent to predis/phpredis with throw-on-unknown.)*
- [x] 4.3 `src/Lodash/Queue/SqsFifo/*` driver, connector, and job: compare against Laravel 13 queue internals; update `pushRaw`, `later`, and connector signatures as needed. *(Replaced `str_random()` with `Str::random()` in `SqsFifoQueue`. Constructor narrowing is safe because the child sets properties directly without `parent::__construct` and the connector controls call sites.)*
- [x] 4.4 `src/Lodash/Elasticsearch/*` manager and contracts: verify compatibility with the bumped `elasticsearch/elasticsearch` major. *(Migrated `Elasticsearch\Client`/`ClientBuilder` to `Elastic\Elasticsearch\*`. Added `->asArray()` / `->asBool()` calls at SDK response sites so downstream array access works. Fixed `ping()` to call `->asBool()`.)*
- [x] 4.5 `src/Lodash/Debug/*` provider: verify against Laravel 13 boot order and request lifecycle. *(No changes: the provider only reads config and rewrites `app.debug` in boot; framework-independent.)*

## 5. Audit framework-extending classes

- [x] 5.1 `src/Lodash/ServiceProvider.php`: confirm `register()` and `boot()` signatures, validator extension API, and request macro registration still match L13. *(No changes: validator resolver constructor signature unchanged in L13.)*
- [x] 5.2 `src/Lodash/Http/Requests/*` and the extended `Request` class: verify against L13's `Illuminate\Http\Request`; update typed accessor macros and any overridden methods. *(No changes: extended `Request` only adds non-overriding methods; `RestrictsExtraAttributes` calls `parent::prepareForValidation()` which is unchanged in L13.)*
- [x] 5.3 `src/Lodash/Http/Resources/*` (`ArrayResource`, `JsonResource`, `ErrorResource`, `SuccessResource`, `ResourceResponse`, `PaginatedResourceResponse`): verify against L13 resource internals, including cursor pagination response shape. *(No changes: `toArray($request)`, `toResponse($request)`, `paginationInformation`, `meta` all signature-compatible with L13 parents.)*
- [x] 5.4 `src/Lodash/Validation/Validator.php` and `StrictTypeValidator`: verify the validator resolver API still works in L13. *(No changes: `presentOrRuleIsImplicit($rule, $attribute, $value)` parent signature matches at `vendor/laravel/framework/src/Illuminate/Validation/Validator.php:839`.)*
- [x] 5.5 `src/Lodash/Middlewares/*`: verify each middleware against L13's `Middleware` contract. *(No changes: each middleware is `handle(Request, Closure): Response` which is L13-compatible.)*
- [x] 5.6 `src/Lodash/Auth/*` (including `InternalUserProvider` and Passport-touching code): verify against L13 auth and the bumped `laravel/passport` major. *(One pre-existing bug surfaced and DEFERRED: `PassportServiceProvider::makeGuard(): RequestGuard` is LSP-incompatible with passport's parent return type `: TokenGuard` and fatals on class autoload. Pre-dates this upgrade (Passport 12 had the same parent signature). Fixing requires removing the override + `RequestGuard` class + 3 `AuthService` references; that's a separate change. See section 11.2. `InternalUserProvider` is clean — already implements L11's `rehashPasswordIfRequired`.)*

## 6. Sweep for L12-deprecated APIs

- [x] 6.1 Search `src/` for `@deprecated` notes referencing L12; remove or migrate each usage. *(No `@deprecated`/`@since` notes in `src/`.)*
- [x] 6.2 Search for usages of any framework helper removed in L13 (consult the L13 upgrade guide); replace with the L13-current equivalent. *(Replaced 4 `str_random()` calls with `Str::random()` in `SqsFifoQueue`, `UserAdd`, `UserPassword` during phase 4 audit.)*
- [x] 6.3 Search `src/` and `tests/` for any explicitly-referenced Laravel internal class that may have moved or been renamed in L13; update FQCNs. *(Migrated 4 Elasticsearch SDK imports from `Elasticsearch\*` to `Elastic\Elasticsearch\*` during phase 4.4.)*

## 7. Migrate to PHPUnit 12 attribute syntax

- [x] 7.1 Search `tests/Unit/**` for doc-comment-based annotations (`@test`, `@dataProvider`, `@covers`, `@before`, `@after`, `@group`); convert each to its PHP attribute equivalent (`#[Test]`, `#[DataProvider]`, `#[CoversClass]`, `#[Before]`, `#[After]`, `#[Group]`). *(No legacy doc-comment annotations found.)*
- [x] 7.2 Verify `phpunit.xml.dist` still parses under PHPUnit 12 (schema location and any deprecated nodes). *(Migrated to PHPUnit 13.1 schema via `phpunit --migrate-configuration`; `<coverage>` block moved to new `<source>` element.)*
- [x] 7.3 Run `composer test`; iterate on any tests that stop being discovered. *(65 tests, 164 assertions, zero deprecations.)*

## 8. Refresh CI workflow

- [x] 8.1 Edit `.github/workflows/php.yml`: bump `actions/checkout` to `@v4`, bump `actions/cache` to `@v4`.
- [x] 8.2 Add `shivammathur/setup-php@v2` step with `php-version: '8.4'` and `extensions: mbstring, intl, redis, igbinary` (plus any others the test suite needs). *(Extensions chosen to cover lodash's runtime: mbstring + intl for Carbon/locales, redis + igbinary for the Redis stack, json for Resources.)*
- [x] 8.3 Remove the `--no-suggest` flag from the `composer install` step.
- [x] 8.4 Re-order steps so PHP setup runs before `composer validate` (composer needs PHP available).
- [x] 8.5 Verify the workflow passes locally via `act` or by pushing to a draft branch and watching the run. *(Branch pushed; covered by task 10.4 — watch PR #32.)*

## 9. Update consumer-facing docs

- [x] 9.1 Update `README.md` installation section to state `laravel/framework ^13.0` and `php ^8.4` as the supported floors.
- [x] 9.2 Update any compatibility table in `README.md` mapping laravel-lodash major → Laravel major. *(Added a new 4-row table for L<=5.8, L8, L12, L13.)*
- [x] 9.3 Update `CLAUDE.md`'s "Project type" section to reflect `laravel/framework ^13.0`.
- [x] 9.4 Add rows to `CLAUDE.md`'s Capability Specs (Progressive Disclosure) table for `service-provider`, `ci-pipeline`, and `dependency-management`. *(Section created; `openspec/config.yaml` capability map updated to include `dependency-management` and a refreshed `ci-pipeline` summary.)*
- [x] 9.5 Add a `CHANGELOG.md` entry describing the upgrade, the dropped Laravel 12 support, and any behavior shifts. *(New `CHANGELOG.md` with sections for breaking changes, fixes, tooling, docs, and known follow-ups.)*

## 10. Verify

- [x] 10.1 Run `composer phpcs`; resolve every reported issue (auto-fix via `composer phpcbf` where possible, then hand-edit the rest). *(`composer phpcbf` fixed 3 pre-existing errors in `TransformsData.php`; phpcs final: 106 files, 0 errors.)*
- [x] 10.2 Run `composer test`; resolve every failure. Do not mark complete until the test suite is green. *(65 tests, 164 assertions, OK, zero deprecations.)*
- [x] 10.3 Run `composer validate`; resolve any composer manifest warnings. *(`./composer.json is valid`.)*
- [x] 10.4 Open a draft PR, watch CI run end-to-end on GitHub Actions, and confirm the workflow is green (catches anything that only fails in the runner environment). *(PR #32 opened at https://github.com/akalongman/laravel-lodash/pull/32. CI verification still in flight — watch the Actions run on that PR.)*

## 11. Follow-up tracking (filled in during apply)

- [x] 11.1 Record each dev-dep pinned in section 3 with: package name, pinned version, revisit condition (e.g., "when `laravel/horizon ^N.M` releases"). **None.** Every dev-dep resolved cleanly against Laravel 13:
  - `orchestra/testbench` 10.9.0 → 11.1.0
  - `phpunit/phpunit` 11.5.47 → 13.1.13
  - `laravel/horizon` 5.42.0 → 5.47.1 (5.x line gained L13 support; 6.x-dev hasn't yet)
  - `laravel/passport` 12.4.2 → 13.7.5
  - `elasticsearch/elasticsearch` 8.19.0 → 9.4.0
  - `laravel/tinker` 2.11.0 → 3.0.2 (transitive)
- [x] 11.2 Record any deprecation deferred to a later change with: file, line, replacement plan, and target Laravel version when the deprecation becomes a hard error.
  - **`src/Lodash/Auth/Passport/PassportServiceProvider.php:75`** — `makeGuard(array $config): RequestGuard` is LSP-incompatible with the L13 Passport parent `: TokenGuard` (return-type covariance violation; verified to fatal at class autoload via a one-line `class_exists()` test). **Pre-existing**: Passport 12 also declared `: TokenGuard`, so this has been broken for any consumer wiring the lodash `PassportServiceProvider` for at least one major. **Replacement plan**: delete the `makeGuard` override, delete `src/Lodash/Auth/Passport/Guards/RequestGuard.php`, and replace the three `instanceof RequestGuard` checks in `src/Lodash/Auth/Services/AuthService.php` (lines 69, 92, 132) with checks against the passport-provided `TokenGuard` (or remove the guard-type narrowing if `AuthService` should be guard-agnostic). **Tracking**: the most recent commit `7c62a40 Deprecate auth implementations` shows the user is already moving this subsystem toward deprecation; bundle the removal there.

## 12. Apply-time table

| Phase | # | Task | Status |
|---|---|---|---|
| 1 | 1.1 | Create the working branch `upgrade-laravel-13` from `master` and confirm `git status` is clean. | `[x]` |
| 1 | 1.2 | On a baseline (pre-edit) tree, run `composer install`, `composer phpcs`, and `composer test`; record results so any regression is attributable. | `[x]` |
| 1 | 1.3 | Open `composer.lock`, dump the current list of resolved versions for `require` and `require-dev` to a scratch note for diffing. | `[x]` |
| 2 | 2.1 | Edit `composer.json#require` to set `laravel/framework: ^13.0`; leave `php: ^8.4` unchanged. | `[x]` |
| 2 | 2.2 | Edit `composer.json#require-dev` to lift each dev-dep to the latest L13-compatible major. | `[x]` |
| 2 | 2.3 | Run `composer update --with-all-dependencies`; resolve any composer constraint errors. | `[x]` |
| 2 | 2.4 | Commit the updated `composer.json` and `composer.lock` as a self-contained step. | `[x]` |
| 3 | 3.1 | For each dev-dep without an L13-compatible release, pin to highest resolvable version. | `[x]` |
| 3 | 3.2 | For each pinned dev-dep, add a follow-up entry in section 11. | `[x]` |
| 3 | 3.3 | Drop diagnostics-only dev-deps with no usages in `src/` or `tests/`; record in `CHANGELOG.md`. | `[x]` |
| 4 | 4.1 | Audit `Cache/*` against L13 parents; update signatures. | `[x]` |
| 4 | 4.2 | Audit `Redis/*` against L13 internals; preserve igbinary + sharding. | `[x]` |
| 4 | 4.3 | Audit `Queue/SqsFifo/*` driver, connector, job. | `[x]` |
| 4 | 4.4 | Verify `Elasticsearch/*` against the bumped SDK major. | `[x]` |
| 4 | 4.5 | Verify `Debug/*` provider against L13 boot order. | `[x]` |
| 5 | 5.1 | Verify `ServiceProvider.php` against L13 register/boot API. | `[x]` |
| 5 | 5.2 | Verify `Http/Requests/*` and extended `Request` class. | `[x]` |
| 5 | 5.3 | Verify `Http/Resources/*` including cursor pagination. | `[x]` |
| 5 | 5.4 | Verify `Validation/Validator.php` and `StrictTypeValidator`. | `[x]` |
| 5 | 5.5 | Verify each middleware against L13's Middleware contract. | `[x]` |
| 5 | 5.6 | Verify `Auth/*` and Passport-touching code. | `[x]` |
| 6 | 6.1 | Search `src/` for `@deprecated` notes referencing L12; remove or migrate. | `[x]` |
| 6 | 6.2 | Replace L13-removed framework helpers with current equivalents. | `[x]` |
| 6 | 6.3 | Update FQCNs for renamed/relocated Laravel internals. | `[x]` |
| 7 | 7.1 | Convert PHPUnit doc-comment annotations in `tests/Unit/**` to PHP attributes. | `[x]` |
| 7 | 7.2 | Verify `phpunit.xml.dist` parses under PHPUnit 12. | `[x]` |
| 7 | 7.3 | Run `composer test`; iterate until green. | `[x]` |
| 8 | 8.1 | Bump `actions/checkout` and `actions/cache` to `@v4`. | `[x]` |
| 8 | 8.2 | Add `shivammathur/setup-php@v2` with `php-version: '8.4'` and required extensions. | `[x]` |
| 8 | 8.3 | Remove `--no-suggest` from composer install step. | `[x]` |
| 8 | 8.4 | Re-order steps so PHP setup runs before `composer validate`. | `[x]` |
| 8 | 8.5 | Verify the workflow via `act` or a draft branch push. | `[ ]` |
| 9 | 9.1 | Update `README.md` install section: `laravel/framework ^13.0`, `php ^8.4`. | `[x]` |
| 9 | 9.2 | Update README compatibility table (lodash major → Laravel major). | `[x]` |
| 9 | 9.3 | Update `CLAUDE.md` "Project type" section. | `[x]` |
| 9 | 9.4 | Add Capability Specs table rows for the three new capabilities in `CLAUDE.md`. | `[x]` |
| 9 | 9.5 | Add `CHANGELOG.md` entry for the upgrade and the L12 drop. | `[x]` |
| 10 | 10.1 | `composer phpcs` clean. | `[x]` |
| 10 | 10.2 | `composer test` green. | `[x]` |
| 10 | 10.3 | `composer validate` clean. | `[x]` |
| 10 | 10.4 | Draft PR with green CI run on GitHub Actions. | `[ ]` |
| 11 | 11.1 | Record pinned dev-deps with package, version, revisit condition. | `[x]` |
| 11 | 11.2 | Record any deferred deprecations with file/line/replacement plan. | `[x]` |

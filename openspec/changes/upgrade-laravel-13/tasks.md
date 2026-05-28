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

- [ ] 3.1 For each dev-dep that has no L13-compatible release, pin it to the highest version composer can resolve against L13.
- [ ] 3.2 For each pinned dev-dep, add a follow-up entry below in section 11 naming the dep and the revisit condition.
- [ ] 3.3 If a stuck dev-dep is used only for diagnostics (no usages in `src/` or `tests/`), drop it from `require-dev` instead of pinning; record the drop in `CHANGELOG.md`.

## 4. Audit extended providers and connectors against L13 parents

- [ ] 4.1 `src/Lodash/Cache/CacheServiceProvider.php` and any `Cache/*` manager/store: compare method signatures against Laravel 13's parent classes; update overrides where the parent changed.
- [ ] 4.2 `src/Lodash/Redis/RedisServiceProvider.php`, `RedisManager`, and any `Redis/Connections|Connectors/*`: compare against Laravel 13 Redis internals; preserve igbinary and client-side sharding behavior.
- [ ] 4.3 `src/Lodash/Queue/SqsFifo/*` driver, connector, and job: compare against Laravel 13 queue internals; update `pushRaw`, `later`, and connector signatures as needed.
- [ ] 4.4 `src/Lodash/Elasticsearch/*` manager and contracts: verify compatibility with the bumped `elasticsearch/elasticsearch` major.
- [ ] 4.5 `src/Lodash/Debug/*` provider: verify against Laravel 13 boot order and request lifecycle.

## 5. Audit framework-extending classes

- [ ] 5.1 `src/Lodash/ServiceProvider.php`: confirm `register()` and `boot()` signatures, validator extension API, and request macro registration still match L13.
- [ ] 5.2 `src/Lodash/Http/Requests/*` and the extended `Request` class: verify against L13's `Illuminate\Http\Request`; update typed accessor macros and any overridden methods.
- [ ] 5.3 `src/Lodash/Http/Resources/*` (`ArrayResource`, `JsonResource`, `ErrorResource`, `SuccessResource`, `ResourceResponse`, `PaginatedResourceResponse`): verify against L13 resource internals, including cursor pagination response shape.
- [ ] 5.4 `src/Lodash/Validation/Validator.php` and `StrictTypeValidator`: verify the validator resolver API still works in L13.
- [ ] 5.5 `src/Lodash/Middlewares/*`: verify each middleware against L13's `Middleware` contract.
- [ ] 5.6 `src/Lodash/Auth/*` (including `InternalUserProvider` and Passport-touching code): verify against L13 auth and the bumped `laravel/passport` major.

## 6. Sweep for L12-deprecated APIs

- [ ] 6.1 Search `src/` for `@deprecated` notes referencing L12; remove or migrate each usage.
- [ ] 6.2 Search for usages of any framework helper removed in L13 (consult the L13 upgrade guide); replace with the L13-current equivalent.
- [ ] 6.3 Search `src/` and `tests/` for any explicitly-referenced Laravel internal class that may have moved or been renamed in L13; update FQCNs.

## 7. Migrate to PHPUnit 12 attribute syntax

- [ ] 7.1 Search `tests/Unit/**` for doc-comment-based annotations (`@test`, `@dataProvider`, `@covers`, `@before`, `@after`, `@group`); convert each to its PHP attribute equivalent (`#[Test]`, `#[DataProvider]`, `#[CoversClass]`, `#[Before]`, `#[After]`, `#[Group]`).
- [ ] 7.2 Verify `phpunit.xml.dist` still parses under PHPUnit 12 (schema location and any deprecated nodes).
- [ ] 7.3 Run `composer test`; iterate on any tests that stop being discovered.

## 8. Refresh CI workflow

- [ ] 8.1 Edit `.github/workflows/php.yml`: bump `actions/checkout` to `@v4`, bump `actions/cache` to `@v4`.
- [ ] 8.2 Add `shivammathur/setup-php@v2` step with `php-version: '8.4'` and `extensions: mbstring, intl, redis, igbinary` (plus any others the test suite needs).
- [ ] 8.3 Remove the `--no-suggest` flag from the `composer install` step.
- [ ] 8.4 Re-order steps so PHP setup runs before `composer validate` (composer needs PHP available).
- [ ] 8.5 Verify the workflow passes locally via `act` or by pushing to a draft branch and watching the run.

## 9. Update consumer-facing docs

- [ ] 9.1 Update `README.md` installation section to state `laravel/framework ^13.0` and `php ^8.4` as the supported floors.
- [ ] 9.2 Update any compatibility table in `README.md` mapping laravel-lodash major → Laravel major.
- [ ] 9.3 Update `CLAUDE.md`'s "Project type" section to reflect `laravel/framework ^13.0`.
- [ ] 9.4 Add rows to `CLAUDE.md`'s Capability Specs (Progressive Disclosure) table for `service-provider`, `ci-pipeline`, and `dependency-management`.
- [ ] 9.5 Add a `CHANGELOG.md` entry describing the upgrade, the dropped Laravel 12 support, and any behavior shifts.

## 10. Verify

- [ ] 10.1 Run `composer phpcs`; resolve every reported issue (auto-fix via `composer phpcbf` where possible, then hand-edit the rest).
- [ ] 10.2 Run `composer test`; resolve every failure. Do not mark complete until the test suite is green.
- [ ] 10.3 Run `composer validate`; resolve any composer manifest warnings.
- [ ] 10.4 Open a draft PR, watch CI run end-to-end on GitHub Actions, and confirm the workflow is green (catches anything that only fails in the runner environment).

## 11. Follow-up tracking (filled in during apply)

- [ ] 11.1 Record each dev-dep pinned in section 3 with: package name, pinned version, revisit condition (e.g., "when `laravel/horizon ^N.M` releases").
- [ ] 11.2 Record any deprecation deferred to a later change with: file, line, replacement plan, and target Laravel version when the deprecation becomes a hard error.

## 12. Apply-time table

| Phase | # | Task | Status |
|---|---|---|---|
| 1 | 1.1 | Create the working branch `upgrade-laravel-13` from `master` and confirm `git status` is clean. | `[ ]` |
| 1 | 1.2 | On a baseline (pre-edit) tree, run `composer install`, `composer phpcs`, and `composer test`; record results so any regression is attributable. | `[ ]` |
| 1 | 1.3 | Open `composer.lock`, dump the current list of resolved versions for `require` and `require-dev` to a scratch note for diffing. | `[ ]` |
| 2 | 2.1 | Edit `composer.json#require` to set `laravel/framework: ^13.0`; leave `php: ^8.4` unchanged. | `[ ]` |
| 2 | 2.2 | Edit `composer.json#require-dev` to lift each dev-dep to the latest L13-compatible major. | `[ ]` |
| 2 | 2.3 | Run `composer update --with-all-dependencies`; resolve any composer constraint errors. | `[ ]` |
| 2 | 2.4 | Commit the updated `composer.json` and `composer.lock` as a self-contained step. | `[ ]` |
| 3 | 3.1 | For each dev-dep without an L13-compatible release, pin to highest resolvable version. | `[ ]` |
| 3 | 3.2 | For each pinned dev-dep, add a follow-up entry in section 11. | `[ ]` |
| 3 | 3.3 | Drop diagnostics-only dev-deps with no usages in `src/` or `tests/`; record in `CHANGELOG.md`. | `[ ]` |
| 4 | 4.1 | Audit `Cache/*` against L13 parents; update signatures. | `[ ]` |
| 4 | 4.2 | Audit `Redis/*` against L13 internals; preserve igbinary + sharding. | `[ ]` |
| 4 | 4.3 | Audit `Queue/SqsFifo/*` driver, connector, job. | `[ ]` |
| 4 | 4.4 | Verify `Elasticsearch/*` against the bumped SDK major. | `[ ]` |
| 4 | 4.5 | Verify `Debug/*` provider against L13 boot order. | `[ ]` |
| 5 | 5.1 | Verify `ServiceProvider.php` against L13 register/boot API. | `[ ]` |
| 5 | 5.2 | Verify `Http/Requests/*` and extended `Request` class. | `[ ]` |
| 5 | 5.3 | Verify `Http/Resources/*` including cursor pagination. | `[ ]` |
| 5 | 5.4 | Verify `Validation/Validator.php` and `StrictTypeValidator`. | `[ ]` |
| 5 | 5.5 | Verify each middleware against L13's Middleware contract. | `[ ]` |
| 5 | 5.6 | Verify `Auth/*` and Passport-touching code. | `[ ]` |
| 6 | 6.1 | Search `src/` for `@deprecated` notes referencing L12; remove or migrate. | `[ ]` |
| 6 | 6.2 | Replace L13-removed framework helpers with current equivalents. | `[ ]` |
| 6 | 6.3 | Update FQCNs for renamed/relocated Laravel internals. | `[ ]` |
| 7 | 7.1 | Convert PHPUnit doc-comment annotations in `tests/Unit/**` to PHP attributes. | `[ ]` |
| 7 | 7.2 | Verify `phpunit.xml.dist` parses under PHPUnit 12. | `[ ]` |
| 7 | 7.3 | Run `composer test`; iterate until green. | `[ ]` |
| 8 | 8.1 | Bump `actions/checkout` and `actions/cache` to `@v4`. | `[ ]` |
| 8 | 8.2 | Add `shivammathur/setup-php@v2` with `php-version: '8.4'` and required extensions. | `[ ]` |
| 8 | 8.3 | Remove `--no-suggest` from composer install step. | `[ ]` |
| 8 | 8.4 | Re-order steps so PHP setup runs before `composer validate`. | `[ ]` |
| 8 | 8.5 | Verify the workflow via `act` or a draft branch push. | `[ ]` |
| 9 | 9.1 | Update `README.md` install section: `laravel/framework ^13.0`, `php ^8.4`. | `[ ]` |
| 9 | 9.2 | Update README compatibility table (lodash major → Laravel major). | `[ ]` |
| 9 | 9.3 | Update `CLAUDE.md` "Project type" section. | `[ ]` |
| 9 | 9.4 | Add Capability Specs table rows for the three new capabilities in `CLAUDE.md`. | `[ ]` |
| 9 | 9.5 | Add `CHANGELOG.md` entry for the upgrade and the L12 drop. | `[ ]` |
| 10 | 10.1 | `composer phpcs` clean. | `[ ]` |
| 10 | 10.2 | `composer test` green. | `[ ]` |
| 10 | 10.3 | `composer validate` clean. | `[ ]` |
| 10 | 10.4 | Draft PR with green CI run on GitHub Actions. | `[ ]` |
| 11 | 11.1 | Record pinned dev-deps with package, version, revisit condition. | `[ ]` |
| 11 | 11.2 | Record any deferred deprecations with file/line/replacement plan. | `[ ]` |

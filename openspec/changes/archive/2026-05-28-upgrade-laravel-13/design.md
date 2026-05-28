## Context

The package currently declares `php ^8.4` and `laravel/framework ^12.0` in `composer.json`. The main `ServiceProvider` is intentionally minimal: it registers commands, request macros, the `strict` validator, and translations, all gated by `lodash.register.*` config flags. Feature providers (`Cache`, `Redis`, `Queue`, `Elasticsearch`, `Debug`) extend their Laravel parents and are wired in by the host application. CI runs a single job on `ubuntu-latest` that validates composer files, caches `vendor/`, and runs `composer phpcs` then `composer test`. There is no PHP-version matrix and no Laravel-version matrix. The repo has no existing capability spec files under `openspec/specs/`.

Two characteristics of this package shape the upgrade:

1. **Drop-in provider replacements.** `Longman\LaravelLodash\Cache\CacheServiceProvider`, `Longman\LaravelLodash\Redis\RedisServiceProvider`, `Longman\LaravelLodash\Queue\QueueServiceProvider`, and friends subclass Laravel's own providers. Method signatures, constructor parameters, and protected method contracts on those parents can change between framework majors, and any divergence is a compile-time error at consumer load time.
2. **Public surface area.** The extended `Request` class, the `Resource` family (`ArrayResource`, `JsonResource`, `ResourceResponse`, `PaginatedResourceResponse`), the `strict` validation rule, and the request macros are all parts of the package's public API. Renames or behavioral drift in Laravel's equivalents must be either re-exposed transparently or documented as breaking.

The user has decided on the following upfront:

- Drop Laravel 12 support entirely; require `^13.0` only.
- Scope includes the composer constraint bump, CI workflow refresh, adoption of new L13 APIs / removal of L12 deprecations, and bumping dev-deps to latest L13-compatible majors.
- Dev-deps without an L13-compatible release stay pinned to the highest available version with a documented follow-up. Drop only if the package is suggested (not required at runtime).
- Capability placement: `service-provider`, `ci-pipeline`, and a new `dependency-management` capability.

## Goals / Non-Goals

**Goals:**

- Ship a release of laravel-lodash whose `require` block is `php ^8.4` and `laravel/framework ^13.0`, with `composer test` green and `composer phpcs` clean.
- Refresh `.github/workflows/php.yml` to use current Action versions (`actions/checkout@v4`, `actions/cache@v4`), explicit PHP setup via `shivammathur/setup-php@v2`, and drop deprecated flags. Single PHP version target, single Laravel version target.
- Land three new capability specs (`service-provider`, `ci-pipeline`, `dependency-management`) that codify the rules being adopted in this change so future upgrades have a reusable contract.
- Sweep extended provider/connector classes against L13 parent signatures; remove L12-deprecated API usages found during the sweep.
- Bump every dev-dep that has an L13-compatible release. Document the ones that do not.

**Non-Goals:**

- Supporting `laravel/framework ^12.0` and `^13.0` simultaneously. The user chose the clean break.
- Bumping the PHP minimum beyond `^8.4`. If L13 itself requires `^8.5`, that constraint propagates naturally via composer resolution, but we do not raise the lodash floor proactively in this change.
- Adding new features. This change is strictly an upgrade and a codification of constraints. Feature work belongs in a separate proposal.
- Refactoring opt-in providers (`Cache`, `Redis`, `Queue`, `Elasticsearch`, `Debug`) beyond what L13 compatibility requires. The "minimum signature-compat sweep" rule applies; gratuitous rewrites are out.
- Restructuring the existing capability map. The three new capabilities slot into the existing taxonomy in `openspec/config.yaml`.
- Adding a Laravel-version matrix to CI. The package targets exactly one Laravel major at a time.

## Decisions

### D1: Drop Laravel 12 instead of supporting `^12 || ^13`

We require `laravel/framework: ^13.0` and remove all L12-compatibility code paths.

*Alternatives considered:*

- **`^12.0 || ^13.0` dual support.** Maximum compatibility for downstream consumers, but it forces version-conditional code in the extended providers (each L12 vs. L13 parent-class divergence becomes a branch), expands the test matrix, and indefinitely couples the package's maintenance burden to two framework versions.
- **`^13.0` only (chosen).** Cleanest. Existing Laravel 12 consumers stay on the prior `^12`-compatible tag of laravel-lodash. The next tag of this package is a clear major-version bump signaling the framework-floor break.

*Rationale:* The package's value is in being a thin, current-Laravel-compatible utility layer. Carrying two framework majors dilutes that value and obscures which APIs are safe to call.

### D2: New `dependency-management` capability rather than folding rules into `service-provider`

Composer-constraint discipline gets its own capability spec, separate from the runtime `service-provider` contract.

*Alternatives considered:*

- **Fold into `service-provider`.** The `service-provider` capability is about *runtime* behavior: opt-in flag gating, command registration, validator wiring. Constraint-management rules are *manifest-level*; they govern `composer.json` and are not enforced at runtime. Mixing the two would conflate "what the provider does when booted" with "what the package declares it depends on."
- **Add a single inline section to CLAUDE.md instead of a capability spec.** Per the Progressive Disclosure rules, if the rule cluster has its own enforcement story (CI gate, recurring upgrade cadence, worked examples), it belongs in a spec. Composer-constraint discipline meets that bar: it has a CI enforcement boundary (`composer validate` plus phpcs/test), it has a recurring application (every Laravel/PHP major bump), and it benefits from worked examples (pin-vs-drop, dev-only vs. runtime).
- **Separate capability (chosen).** Clean separation of concerns. `service-provider` keeps its scope ("the runtime contract of the main provider"), and `dependency-management` owns the manifest discipline. Future upgrades reuse `dependency-management` without bloating `service-provider`.

*Rationale:* These two specs serve different audiences and different enforcement boundaries. Splitting them keeps each spec focused.

### D3: Stuck dev-deps are pinned at highest available, not blocked

If a dev-dep (e.g., `laravel/horizon`, `laravel/passport`) has not shipped an L13-compatible release by the time we apply this change, we pin it at the highest version we can resolve against L13 and add a follow-up task to revisit. We do not block the upgrade waiting for the full ecosystem.

*Alternatives considered:*

- **Block until all deps are L13-ready.** Cleanest end-state, but the package's release cadence becomes hostage to the slowest dev-dep. Worse: dev-deps are not runtime requirements; users do not load `laravel/horizon` from inside this package.
- **Drop incompatible dev-deps entirely.** Acceptable only when the package is *suggested* (not required at runtime) and only used in dev/tests for diagnostic value. We apply this on a case-by-case basis.
- **Pin to highest available, document follow-up (chosen).** Lets the upgrade ship while keeping a documented audit trail.

*Rationale:* Dev-deps are CI scaffolding, not part of the package's public contract. Their lag should not gate a framework upgrade.

### D4: CI gets a refresh, not a matrix

We update `actions/checkout` and `actions/cache` to v4, add `shivammathur/setup-php@v2` with the package's minimum PHP version, drop `--no-suggest`, and keep the workflow as a single job. No PHP-version matrix and no Laravel-version matrix.

*Alternatives considered:*

- **PHP-version matrix (`8.4`, `8.5` once available).** Useful for catching version-specific regressions, but the package's `php` constraint is `^8.4`, and the runtime contract is "we work on any PHP 8.4+ that L13 accepts." A matrix doubles CI time for marginal additional coverage on a small package.
- **Laravel-version matrix.** Explicitly out-of-scope per D1.
- **Single-job refresh (chosen).** Minimal, addresses the action-version technical debt, keeps the workflow easy to reason about.

*Rationale:* CI exists to enforce the package's *current* contract. The current contract is "one PHP floor, one Laravel major." A matrix would document a contract we are not making.

### D5: Sweep extended providers/connectors before sweeping leaf code

When tests fail after the constraint bump, the audit order is: extended providers (`Cache`, `Redis`, `Queue/SqsFifo`, `Elasticsearch`, `Debug`) and connectors first, because those are most likely to break on parent-signature changes; then `Http\Request`, `Http\Resources/*`, and `Validation\Validator`, because those subclass framework classes whose contracts have evolved; then leaf utilities (`Support`, `Eloquent` traits, `Middlewares`). Tests and `phpcs` form the boundary.

*Rationale:* Compile-time errors in extended providers manifest as fatal errors before tests can run. Fixing them first surfaces downstream test failures cleanly.

### D6: Specs are born-populated with the change's new requirements

The three capability specs we add do not stub empty `ADDED Requirements` deltas. Per the OpenSpec project rules, extracting existing inline conventions and adding the new normative requirements together produces a real, populated spec. Each spec carries its own requirements (framework floor, opt-in flag discipline, CI step contract, constraint-management policy) with `WHEN/THEN` scenarios.

*Rationale:* Empty stubs fail validation and force artificial `ADDED Requirements` deltas. This change is the right time to populate them because the upgrade *is* the new requirement.

## Risks / Trade-offs

- **Risk: L12-only consumers cannot upgrade laravel-lodash without first upgrading Laravel.** → Mitigation: tag this release as a clear major-version bump in laravel-lodash. Update README compatibility table to map "laravel-lodash MAJOR → Laravel framework MAJOR" so consumers can pick the right tag at a glance.

- **Risk: Extended-provider signature drift may force a behavioral change consumers see at runtime.** → Mitigation: when fixing a signature, default to preserving observable behavior; document any unavoidable behavior change in `CHANGELOG.md` and in the relevant capability spec's `REMOVED` or `MODIFIED` requirements (if applicable in a future change). For this change, surface them in the proposal's Impact section as Affected Code.

- **Risk: Dev-deps without L13-compatible releases force pin-at-highest, which can mask incompatibilities in tests that depend on those deps.** → Mitigation: document each pin with a `// FIXME: revisit after <dep> ships L13-compatible release` comment in `composer.json` (acceptable in JSON since composer ignores comments-in-non-strict mode is false — actually use a top-of-file `composer.json` doc comment is not possible; instead add a `notes/dependency-pins.md` or capture the list in tasks.md and the new `dependency-management` spec).

- **Risk: PHPUnit 12 deprecates legacy doc-comment-based test annotations in favor of attributes.** → Mitigation: as part of the sweep, audit `tests/Unit/**` for `@test`, `@dataProvider`, `@covers`, `@before`, etc. and migrate to PHP attribute syntax. If any test stops being discovered, the test run fails closed.

- **Risk: `orchestra/testbench` major bump may change the test-bootstrap surface (`TestCase` base class, env setup).** → Mitigation: review `tests/Bootstrap.php` and `tests/Unit/TestCase.php` against the new testbench's docs in the same PR; commit the testbench bump in a self-contained step so any failures are isolated and bisectable.

- **Risk: `laravel/passport` and `laravel/horizon` historically lag Laravel majors.** → Mitigation: D3 covers this. The follow-up task list in `tasks.md` captures the revisit, and the `dependency-management` spec defines the pin-and-follow-up rule normatively so future upgrades inherit the same playbook.

- **Trade-off: dropping L12 support strands consumers who are not ready to upgrade their host app.** Accepted. The previous `^12`-compatible tag of laravel-lodash remains installable; consumers can stay on it until they migrate.

- **Trade-off: refreshing CI without adding a matrix means we trade coverage breadth for workflow simplicity.** Accepted per D4.

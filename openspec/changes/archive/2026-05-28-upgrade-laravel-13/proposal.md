## Why

Laravel 13 is the current major release of the framework, and the laravel-lodash package is still pinned to `laravel/framework ^12.0`. Holding back leaves consumers stuck on an older framework when they want to adopt L13, blocks the package from benefiting from L13 ecosystem improvements, and lets dev-dependency versions drift further out of date. Upgrading now (before more downstream churn accumulates) keeps the upgrade tractable and gives us an opportunity to codify the composer-constraint discipline as a first-class capability so future framework bumps follow the same playbook.

## What Changes

- **BREAKING** Drop support for `laravel/framework ^12.0`. The `require` block becomes `laravel/framework: ^13.0`. Consumers on Laravel 12 stay on the last `^12`-compatible tag of this package.
- Lift dev-dependency floors to the latest L13-compatible majors: `orchestra/testbench`, `phpunit/phpunit`, `laravel/horizon`, `laravel/passport`, `aws/aws-sdk-php`, `elasticsearch/elasticsearch`, `google/apiclient`, `longman/php-code-style`, `mockery/mockery`, `neitanod/forceutf8`, `jetbrains/phpstorm-attributes`. Any dev-dep without an L13-compatible release is pinned to its highest available version with a follow-up task to revisit when upstream catches up.
- Adopt new L13 APIs and remove usages of L12-deprecated APIs across the codebase. Includes signature audits of the extended Cache, Redis, Queue (SqsFifo), and Elasticsearch providers against their L13 parent classes; updates to any deprecated `Illuminate\*` calls; and refreshes to PHPUnit attribute syntax where v12 expects it.
- Refresh `.github/workflows/php.yml`: bump `actions/checkout` and `actions/cache` to v4, drop the deprecated `--no-suggest` flag, set up PHP explicitly via `shivammathur/setup-php`, and run the test suite (no Laravel-version matrix; the package targets exactly one Laravel major at a time).
- Update `CLAUDE.md` to reflect the new framework and PHP floor, and update `README.md` install/compatibility notes accordingly.
- Tighten composer-constraint discipline going forward: capture the rules for declaring framework/PHP floors, gating opt-in providers behind config flags, and treating CI as the enforcement boundary.

## Capabilities

### New Capabilities

- `service-provider`: Package bootstrap and composer-manifest contract. Born-populated by extracting existing inline conventions (composer constraints, `lodash.register.*` opt-in flags, main provider's minimal role) from CLAUDE.md and codifying the new `laravel/framework ^13.0` floor. Justified because this change is the first time the constraint itself becomes a normative requirement.
- `ci-pipeline`: GitHub Actions contract for the package. Born-populated by extracting the existing workflow conventions (validate composer files, cache vendor, run phpcs then test) and adding the refreshed action versions and PHP setup step. Justified because the workflow file is changing as part of this proposal and the rules need a home.
- `dependency-management`: Cross-cutting composer-dependency discipline. New capability covering how `require` and `require-dev` constraints are chosen, what the "drop incompatible dev-deps vs. pin to highest available" decision rule is, and how upgrades are sequenced. Justified because composer-constraint discipline is not framework-specific, will keep being relevant for every subsequent PHP/Laravel/dep major bump, and does not fit cleanly inside `service-provider` (which is about the runtime provider contract, not the manifest-management policy).

### Modified Capabilities

None. The repo's `openspec/specs/` directory has no existing capability spec files yet (specs emerge from real changes per the project convention), so every capability touched here is genuinely new.

## Impact

- **composer.json** — `require.laravel/framework` bumped to `^13.0`; multiple `require-dev` floors raised; `php` constraint reaffirmed at `^8.4`.
- **composer.lock** — regenerated.
- **`.github/workflows/php.yml`** — actions/checkout v4, actions/cache v4, `shivammathur/setup-php` added, `--no-suggest` removed.
- **Source code under `src/Lodash/`** — targeted edits where L12-deprecated APIs are used or where extended provider/connector signatures diverge from their L13 parents. Expected hotspots: `Cache/`, `Redis/`, `Queue/SqsFifo/`, `ServiceProvider.php`, `Http/Request*`, `Http/Resources/*`, `Validation/Validator.php`.
- **Tests under `tests/Unit/`** — adjustments for any phpunit 12 attribute/syntax change and for any behavior shift in extended-provider tests; `tests/Bootstrap.php` and `tests/Unit/TestCase.php` reviewed against `orchestra/testbench` major bump.
- **CLAUDE.md** — Project Type section reflects new framework/PHP floor; Capability Specs (Progressive Disclosure) table gains rows for the three new specs.
- **README.md** — install instructions and compatibility table updated.
- **Downstream consumers** — Anyone on Laravel 12 must stay on the previous tag of laravel-lodash. The release should be tagged as a major version bump in this package so SemVer signals the framework-floor break.
- **Optional dev-deps** — Any dev-dep we cannot lift to an L13-compatible version is either pinned (with a documented follow-up) or dropped if it is only suggested at runtime.

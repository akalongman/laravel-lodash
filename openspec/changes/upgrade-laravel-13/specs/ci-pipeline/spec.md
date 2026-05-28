## ADDED Requirements

### Requirement: GitHub Actions workflow file location and triggers

The package SHALL maintain a CI workflow at `.github/workflows/php.yml` that runs on `push` and `pull_request` against the `master` branch. The workflow MUST execute on `ubuntu-latest` as a single job (no PHP-version matrix and no Laravel-version matrix), because the package targets exactly one Laravel major and one PHP floor at a time.

#### Scenario: Workflow triggers on push to master

- **WHEN** a commit is pushed to the `master` branch
- **THEN** the `PHP Composer` workflow SHALL run
- **AND** the workflow SHALL run on `ubuntu-latest` runner

#### Scenario: Workflow triggers on pull request to master

- **WHEN** a pull request targeting `master` is opened or updated
- **THEN** the same workflow SHALL run with the same steps

### Requirement: Explicit PHP setup via `shivammathur/setup-php`

The workflow SHALL set up PHP explicitly via `shivammathur/setup-php@v2` rather than relying on the runner's default PHP. The PHP version configured in the setup step SHALL match the package's declared minimum (currently `8.4`).

#### Scenario: PHP version matches the package minimum

- **WHEN** a contributor reads `.github/workflows/php.yml`
- **THEN** the `shivammathur/setup-php@v2` step SHALL set `php-version` to a value matching `composer.json#require.php`'s floor
- **AND** if `composer.json#require.php` is bumped in a future change, the workflow `php-version` SHALL be bumped in the same change

### Requirement: Current Action versions

The workflow SHALL pin third-party Actions to current major versions: `actions/checkout@v4` and `actions/cache@v4`. The workflow MUST NOT pin to deprecated `@v2` or older versions.

#### Scenario: Workflow uses current Action versions

- **WHEN** a contributor reads `.github/workflows/php.yml`
- **THEN** every third-party Action reference SHALL pin to its current major (at least `@v4` for `actions/checkout` and `actions/cache`)

### Requirement: Composer validation, install, lint, and test sequence

The workflow SHALL execute these steps in order: (1) `composer validate` to verify `composer.json` and `composer.lock`, (2) restore-from-cache or `composer install --prefer-dist --no-progress`, (3) `composer run-script phpcs` to lint, (4) `composer run-script test` to run the test suite. The deprecated `--no-suggest` flag MUST NOT be used.

#### Scenario: A pushed commit that fails composer validate

- **WHEN** `composer.json` or `composer.lock` is invalid
- **THEN** the `composer validate` step SHALL fail
- **AND** subsequent steps SHALL be skipped
- **AND** the workflow SHALL report failure

#### Scenario: A pushed commit that fails phpcs

- **WHEN** the diff introduces a code-style violation
- **THEN** the `composer run-script phpcs` step SHALL fail
- **AND** the `composer run-script test` step SHALL still run (so contributors see both lint and test failures in one CI run)

#### Scenario: A pushed commit that fails the test suite

- **WHEN** the diff causes a PHPUnit failure
- **THEN** the `composer run-script test` step SHALL fail
- **AND** the workflow SHALL report failure

#### Scenario: `--no-suggest` is not used

- **WHEN** a contributor reads any composer install step in the workflow
- **THEN** the `--no-suggest` flag SHALL NOT appear (it is deprecated in composer 2 and a no-op)

### Requirement: Vendor directory caching

The workflow SHALL cache the `vendor/` directory keyed on a hash of `composer.lock`. Cache restoration SHALL be attempted before `composer install`, and `composer install` SHALL run only on cache miss.

#### Scenario: Cache hit skips composer install

- **WHEN** the workflow runs and the `composer.lock` hash matches a previously cached entry
- **THEN** the cache restore SHALL succeed
- **AND** the `composer install` step SHALL be skipped (`if: steps.composer-cache.outputs.cache-hit != 'true'`)

#### Scenario: Cache miss runs composer install

- **WHEN** `composer.lock` has changed since the last cached run
- **THEN** the cache restore SHALL miss
- **AND** the `composer install` step SHALL run and populate the cache for the next run

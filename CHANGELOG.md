# Changelog

All notable changes to `longman/laravel-lodash` are documented in this file.

## [16.0.0] - 2026-08-31

### Changed (BREAKING)

- Size rules on an attribute declared with a numeric `strict` type (`strict:int`, `strict:integer`, `strict:float`, `strict:double`) now compare the value as a number and report the numeric failure message. Previously they compared it as text.

  **A bound that read as an enforced limit was inert.** Laravel's `Validator::getSize()` switches into numeric mode only when the attribute also carries one of the framework's own numeric rules (`numeric`, `integer`, `decimal`), and a rule registered through the validator resolver is invisible to that check. So `['strict:integer', 'max:32767']` measured `mb_strlen($value)` and admitted `2147483648`, `['strict:integer', 'min:1']` admitted `0`, and `['strict:integer', 'between:1,10']` admitted `999`. Nothing errored and nothing was logged; the limit simply did not apply. This surfaced as a security fix in a consuming application, where a value too large for its database column passed validation and was refused only by the database.

  **Requests that were being accepted will start being rejected.** That is the intent of the change, but any consumer relying on the inert bounds, even accidentally, sees new validation failures on upgrade. The failure message changes with it: `max` on such an attribute now renders `validation.max.numeric` ("must not be greater than 32767") rather than `validation.max.string` ("must not be greater than 32767 characters").

  **Test suites are where this surfaces first.** A test asserting that an out-of-range value passed validation on a strict-numeric attribute was asserting the defect; correct the expectation rather than reverting. Consumers who worked around this by declaring the type twice, `['strict:integer', 'integer', 'max:32767']`, see no behavioural change at all and may drop the now-redundant `integer` rule whenever convenient.

  Nothing else moves. `strict:string`, `strict:boolean`, `strict:array` and attributes carrying no `strict` rule keep the framework's default sizing: strings by character length, arrays by element count. The `gt`, `gte`, `lt` and `lte` comparisons already behaved numerically on strict attributes, because the framework's own `shouldBeNumeric()` covers them; they are now pinned by tests so that behaviour is guaranteed rather than incidental. `StrictTypeValidator` and the accepted type aliases are unchanged.

## [15.1.0] - 2026-08-06

### Fixed

- `Http\Resources\JsonResource` no longer drops a nested include when a shorter path sharing its prefix appears later in the include list. Include paths now compose as a set: `['accounts.degree', 'accounts']` and `['accounts', 'accounts.degree']` render the same payload, and the tree is the union of every requested path regardless of order.

  **The pattern that triggers it is `array_merge($defaultIncludes, $request->getValidatedIncludes())`.** A controller declaring `['criteria', 'criteria.courseComponent']` as always-on defaults, merged with a client sending `?include=criteria`, produced `['criteria', 'criteria.courseComponent', 'criteria']`. The trailing `criteria` flattened the subtree the second entry had built, so `criteria` rendered with no `courseComponent` at all. Asking for more data returned less, with no error and nothing logged. The ordering requirement was invisible at the call site, which is why reversing the merge order is a workaround rather than a fix: it also does not cover a child resource whose own `$includedRelations` is merged with the chain passed down to it.

  This is a widening, not a reshape. Every include list that previously produced a complete tree still produces it; only paths that were being silently discarded now survive. **Test suites are where this surfaces.** `Testing\Response`'s resource assertions default to `exact: true`, so a declared structure that omitted a relationship which was being dropped will newly fail on an unexpected key. Declare the recovered relationship rather than reverting, using the `?` nullable marker added in 15.0.0 where the relationship can also be empty. Any test asserting a nested relationship was absent under a prefix include was asserting the defect.

  The order in which keys appear inside the rendered `relationships` object is not part of the contract. First-seen order is preserved in practice and nothing changes on the wire today, but consumers should not compare serialized payloads byte-for-byte across differently-ordered include lists.

## [15.0.0] - 2026-08-06

### Changed (BREAKING)

- `Http\Resources\JsonResource` renders a requested-but-empty relationship as `null` under its key instead of omitting the key. An `includeXxx()` method returning `null` previously dropped the relationship from the `relationships` block entirely, which a client could not distinguish from the include being ignored, the server declining to serve it, or a bug. A populated relationship is still reached at `relationships.<name>.data`; an empty one is now `null` at `relationships.<name>`. Relationships the client did not request stay absent, so payload size is unchanged for them.

  **Test suites break before client code does.** `Testing\Response::assertJsonDataResource()` and `assertJsonDataResources()` default to `exact: true`, so any test whose declared structure omitted a nullable relation, which was the only way to make it pass previously, now fails on the unexpected key. Migrate by declaring the relation with the new `?` marker, `['avatar?']`, instead of leaving it out. Client code branching on key presence (`array_key_exists`, `'avatar' in relationships`, `hasOwnProperty`) also changes behaviour; code reading `relationships.avatar?.data?.url` is unaffected.

  The `return new XResource(null)` idiom already produced the new shape, because Laravel's `ConditionallyLoadsAttributes::filter()` collapses a resource wrapping a null model, so the two idioms now agree. Consumers who need the corrected shape before upgrading can rewrite a nullable `includeXxx()` from a null-guarded ternary to `return new XResource($model->relation);`, which produces the same output on 14.x and stays correct after the upgrade.

- `Testing\DataStructuresProvider::parseSegment()` returns a fourth element carrying the nullable flag, and the normalized relation tree gained a `nullable` key. Subclasses overriding these protected members must update accordingly.

### Added

- `Testing\DataStructuresProvider` relation includes accept a `?` suffix marking a nullable single relation (`'avatar?'`, `'avatar?:AdminAvatar'`, `'accounts?.degree'`). `Testing\Response` resolves the marker against the actual payload, so one declared structure covers both the populated and the empty case under exact and non-exact matching, while a missing relationship key still fails with a message naming the relation. Combining the marker with the collection marker (`'roles[]?'`, `'roles?[]'`) throws `InvalidArgumentException`, since a relationship collection never renders as `null`.

### Tooling

- Raise the `phpunit/phpunit` dev constraint from `^13.0` to `^13.2`: the test suite uses `expectExceptionMessageIsOrContains()`, introduced in PHPUnit 13.2. The 14.0.0 CI run failed against an older locked PHPUnit; the 14.0.1 lock refresh already resolved the failure, and this records the actual floor.

## [14.0.1] - 2026-07-08

### Fixed

- Refresh `composer.lock` to the latest resolvable versions (`laravel/framework` 13.19.0, `aws/aws-sdk-php` 3.388.0, `phpunit/phpunit` 13.2.4, `guzzlehttp/guzzle` 7.13.2, and transitive updates). Resolves four Dependabot alerts: guzzle dot-only cookie-domain matching and silent HTTPS-proxy downgrade (GHSA-cwxw-98qj-8qjx, GHSA-wpwq-4j6v-78m3), psr7 CRLF injection (GHSA-vm85-hxw5-5432), and phpseclib SSRF via X.509 AIA (GHSA-m557-wrgg-6rp4). No `composer.json` constraint changes; no direct dependency has a newer major available.

## [14.0.0] - 2026-07-08

### Changed (BREAKING)

- Require `laravel/framework ^13.0`. Drops support for Laravel 12. Consumers on Laravel 12 stay on the `^12.2` tag of this package. (Corrected on 2026-08-06: this entry originally pointed at `^13.x`, which is wrong, because 13.0.0 already required `laravel/framework ^13.0`. See the compatibility table in the README.)
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

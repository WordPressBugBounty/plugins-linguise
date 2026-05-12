# Script-PHP — Testing

## Framework & Configuration

- **Framework**: PHPUnit 9 (`phpunit/phpunit ^9`)
- **Config file**: `phpunit.xml`
- **Bootstrap**: `./vendor/autoload.php`
- **Coverage source**: `src/` directory, suffix `.php`
- **Coverage output**: HTML → `tests/reports/`, Clover XML → `tests/reports/reports.xml`
- **Flags**: `colors=true`, `verbose=true`, `failOnRisky=true`, `failOnWarning=true`

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run only the root test suite
./vendor/bin/phpunit --testsuite root

# Run only platform adapter tests
./vendor/bin/phpunit --testsuite platforms

# Run a single test file
./vendor/bin/phpunit tests/ProcessorTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testMethodName tests/ProcessorTest.php

# Run with coverage (requires Xdebug or pcov)
./vendor/bin/phpunit --coverage-html tests/reports/
```

## Test Suites

| Suite | Source | Description |
|-------|--------|-------------|
| `root` | `./tests/*.php` | All core class tests |
| `platforms` | `./tests/Platforms/*.php` | CMS platform adapter tests |

## Test File Map

Tests mirror the `src/` structure 1-to-1:

| Test file | Subject |
|-----------|---------|
| `BoundaryTest.php` | Multipart body builder |
| `CacheTest.php` | Cache hash, serve, save, eviction |
| `CertificatesTest.php` | Certificate download and freshness |
| `CmsDetectTest.php` | CMS detection per filesystem marker |
| `ConfigurationTest.php` | Singleton get/set, `loadFile()` |
| `CurlMultiTest.php` | Parallel curl orchestration |
| `CurlRequestTest.php` | Proxy request construction |
| `DatabaseTest.php` | DB facade driver selection |
| `DebugTest.php` | Log verbosity and IP filtering |
| `DeferTest.php` | Deferred action queue |
| `HelperTest.php` | Utility methods |
| `HookTest.php` | Hook add/trigger/wait |
| `HttpResponseTest.php` | JSON/HTML emit helpers |
| `JsonWalkerTest.php` | JSON traversal and fragment wrapping |
| `ManagementTest.php` | Admin POST action handlers |
| `OobeManagerTest.php` | OOBE flow and wizard state |
| `ProcessorTest.php` | Full translation orchestration |
| `RequestTest.php` | URL parsing per CMS |
| `ResponseTest.php` | Header/cookie accumulation |
| `SessionTest.php` | Admin authentication and CSRF |
| `SetCookieTest.php` | Cookie string parsing |
| `TranslationTest.php` | Translation API call and response handling |
| `UrlTest.php` | URL redirect translation |
| `Databases/MysqlTest.php` | MySQL driver (mysqli) |
| `Databases/SqliteTest.php` | SQLite driver |
| `Platforms/PrestaShopTest.php` | PrestaShop adapter |
| `Platforms/OpenCartTest.php` | OpenCart adapter |
| `Platforms/ZencartTest.php` | Zencart adapter |

## Test Helpers

| File | Purpose |
|------|---------|
| `tests/CurlStub.php` | Replaces `curl_*` functions with controllable stubs to avoid real HTTP calls |
| `tests/DatabaseHelper.php` | Sets up and tears down test databases (MySQL + SQLite) |
| `tests/Helper.php` | General test utilities shared across test files |
| `tests/NamespaceStub.php` | Stubs PHP built-ins in the `Linguise\Script\Core` namespace (`header()`, `setcookie()`, `session_*`, etc.) |

## Fixture Data

- `tests/data/` — HTML, JSON, PHP snippet fixtures used as inputs to tests
- `tests/templates/` — Template stub files for management UI rendering tests

## Environment Variables in Tests

Injected by `phpunit.xml` via `<env>` tags. These allow MySQL integration tests to connect:

```xml
<env name="DB_HOST" value="localhost"/>
<env name="DB_NAME" value="linguise_tests"/>
<env name="DB_USER" value="root"/>
<env name="DB_PASSWORD" value="password"/>
<env name="DB_DBNAME" value="testing"/>
```

Override by setting environment variables before running PHPUnit:

```bash
DB_HOST=my-host DB_USER=myuser ./vendor/bin/phpunit
```

## Mocking Patterns

### Curl Mocking

Script-PHP uses a namespace-function-override pattern. `CurlStub.php` defines replacements for `curl_init`, `curl_setopt`, `curl_exec`, `curl_getinfo`, `curl_close`, etc. in the `Linguise\Script\Core` namespace. This means tests can control exactly what curl returns without any real HTTP traffic.

```php
// Example: make CurlRequest return a specific HTTP response
CurlStub::setResponse(200, 'HTTP/1.1 200 OK', '<html>...</html>');
```

### Namespace Stubs

`NamespaceStub.php` overrides functions like `header()`, `setcookie()`, `session_start()`, `session_destroy()` in the `Linguise\Script\Core` namespace so tests can assert on calls without triggering real PHP session/header side effects.

### Singleton Reset

Each test that uses a singleton (`Configuration`, `Request`, `Response`, `Cache`, etc.) must reset it between tests. The pattern is:

```php
protected function setUp(): void
{
    Configuration::getInstance()->reset();
    Request::getInstance()->reset();
    Response::getInstance()->reset();
}
```

### Filesystem Tests

Tests that write cache files or the debug log use `sys_get_temp_dir()` for the data directory and clean up in `tearDown()`.

## Best Practices

- Always write a test when adding a new public method to a core class
- Use `CurlStub` for any test involving `CurlRequest` or `Translation` — never make real HTTP calls
- Use `NamespaceStub` to intercept `header()` and `setcookie()` calls in `Response` tests
- Reset all singletons in `setUp()` to prevent test bleed
- New platform adapters (in `src/Platforms/`) must have a corresponding test in `tests/Platforms/`

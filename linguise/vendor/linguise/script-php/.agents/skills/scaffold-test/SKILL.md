---
name: scaffold-test
description: Generates a PHPUnit test file for a new src/ class. Reads reference test files (ProcessorTest, CacheTest, ManagementTest, PrestaShopTest, CurlStub, NamespaceStub, DatabaseHelper, phpunit.xml) to detect required imports and singleton resets, then produces a complete test file following all Script-PHP conventions.
---

# Scaffold Test

Generates a PHPUnit 9 test file for a new `src/` class, following the strict conventions documented in `memory-bank/testing.md`.

## Procedure

### Step 1 — Read reference files (read-only, do not modify)

Read these files to understand the current conventions:
1. `tests/ProcessorTest.php` — complex test with stub singletons pattern
2. `tests/CacheTest.php` — simpler test with resetSingleton() pattern
3. `tests/ManagementTest.php` — test with explicit reset methods, CurlStub, NamespaceStub
4. `tests/Platforms/PrestaShopTest.php` — platform adapter test pattern
5. `tests/CurlStub.php` — the CurlHandleStub class (in namespace `Linguise\Script\Core`)
6. `tests/NamespaceStub.php` — the NamespaceStub class (in namespace `Linguise\Script\Core`)
7. `tests/DatabaseHelper.php` — DatabaseHelper class for DB-integration tests
8. `phpunit.xml` — test suite configuration and env variables

### Step 2 — Determine the target class

Ask the user for the target class name (e.g., `MyNewClass`). Derive:
- **Test class name**: `<TargetClass>Test`
- **Test file path**: `tests/<TargetClass>Test.php` (mirrors `src/` structure; use `tests/Platforms/` for platform classes)
- **Test namespace**: `Linguise\Script\Core` (or `Linguise\Script\Core\Platforms` for platform classes)
- **Covers annotation**: `@covers \\Linguise\\Script\\Core\\<TargetClass>` (or `\\Linguise\\Script\\Core\\Platforms\\<TargetClass>`)


### Step 3 — Scan the target src/ class for dependencies

Read `src/<TargetClass>.php` and check its `use` imports and code for these patterns:

| If the target class references... | Then include in the test... |
|-----------------------------------|----------------------------|
| `CurlRequest` or `Translation` | `use Linguise\Script\Core\CurlHandleStub;` + `include_once(dirname(__FILE__) . '/CurlStub.php');` + `CurlHandleStub::resetCurrentInstance()` in `setUp()` |
| `Response` or `Session` or calls `header()`, `setcookie()`, `session_*()`, `http_response_code()` in namespace | `use Linguise\Script\Core\NamespaceStub;` + `include_once(dirname(__FILE__) . '/NamespaceStub.php');` + `NamespaceStub::reset()` in `setUp()` and `tearDown()` |
| `Database` | Singleton reset for `Database::class` in `setUp()` |
| `Configuration` | Singleton reset for `Configuration::class` in `setUp()` |
| `Request` | Singleton reset for `Request::class` in `setUp()` |
| `Response` | Singleton reset for `Response::class` in `setUp()` |
| `Cache` | Singleton reset for `Cache::class` in `setUp()` |
| `Session` | Singleton reset for `Session::class` in `setUp()` |
| `Management` | Singleton reset for `Management::class` in `setUp()` |

### Step 4 — Generate the test file

Use the template below, filling in the detected dependencies from Step 3.

#### Required `use` statements (based on detection)

Add these based on Step 3 detection:
- `use PHPUnit\Framework\TestCase;` — always required
- `use ReflectionClass;` — always required (for resetSingleton helper)
- `use Linguise\Script\Core\CurlHandleStub;` — if CurlRequest/Translation referenced
- `use Linguise\Script\Core\NamespaceStub;` — if header/setcookie/session functions used
- `use Linguise\Script\Core\{TargetClass};` — always include the subject class
- `use Linguise\Script\Core\Configuration;` — if Configuration is a dependency
- `use Linguise\Script\Core\Request;` — if Request is a dependency
- `use Linguise\Script\Core\Response;` — if Response is a dependency
- `use Linguise\Script\Core\Cache;` — if Cache is a dependency
- `use Linguise\Script\Core\Database;` — if Database is a dependency
- `use Linguise\Script\Core\Session;` — if Session is a dependency
- `use Linguise\Script\Core\Management;` — if Management is a dependency

#### Required `include_once` statements (based on detection)

- `include_once(dirname(__FILE__) . '/CurlStub.php');` — if CurlRequest/Translation referenced
- `include_once(dirname(__FILE__) . '/NamespaceStub.php');` — if header/setcookie/session functions used
- For platform tests: use `dirname(__FILE__) . '/../CurlStub.php'` and `dirname(__FILE__) . '/../NamespaceStub.php'`

#### Singleton reset lines

One line per singleton detected, using `$this->resetSingleton(ClassName::class);`.

If the target class itself is a singleton, do NOT reset its own `_instance` in `setUp()` — add it only to `tearDown()` to clean up after the test.

#### Code template

```php
<?php

declare(strict_types=1);

namespace {NAMESPACE};

use PHPUnit\Framework\TestCase;
use ReflectionClass;
{ADDITIONAL_USE_STATEMENTS}

{INCLUDE_STATEMENTS}

/**
 * @covers {COVERS_CLASS}
 */
final class {TEST_CLASS_NAME} extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
            define('LINGUISE_SCRIPT_TRANSLATION', true);
        }

        if (!defined('LINGUISE_SCRIPT_TESTING')) {
            define('LINGUISE_SCRIPT_TESTING', true);
        }

        if (!defined('LINGUISE_SCRIPT_TRANSLATION_VERSION')) {
            define('LINGUISE_SCRIPT_TRANSLATION_VERSION', 'test-version');
        }

        include_once(dirname(__FILE__) . '/Helper.php');
    }

    protected function setUp(): void
    {
        parent::setUp();

        {CURLSTUB_RESET}
        {NAMESPACESTUB_RESET}

        // Reset singletons
        {SINGLETON_RESETS}

        // Configure Configuration singleton
        {CONFIG_SETUP}
    }

    protected function tearDown(): void
    {
        // Reset singletons
        {SINGLETON_RESETS}
        {NAMESPACESTUB_RESET}

        parent::tearDown();
    }

    private function resetSingleton(string $className): void
    {
        $reflection = new ReflectionClass($className);
        $instanceProperty = $reflection->getProperty('_instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null);
    }

    // TODO: Add test methods here
    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = {TARGET_CLASS}::getInstance();
        $instance2 = {TARGET_CLASS}::getInstance();

        $this->assertInstanceOf({TARGET_CLASS}::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }
}
```


#### Template variable reference

| Variable | Description |
|----------|-------------|
| `{NAMESPACE}` | `Linguise\Script\Core` for root tests, `Linguise\Script\Core\Platforms` for platform tests |
| `{ADDITIONAL_USE_STATEMENTS}` | One per line from the detection table in Step 3 |
| `{INCLUDE_STATEMENTS}` | `include_once(...)` lines for CurlStub and NamespaceStub as needed |
| `{COVERS_CLASS}` | Full qualified class name, e.g., `\\Linguise\\Script\\Core\\MyNewClass` |
| `{TEST_CLASS_NAME}` | `<TargetClass>Test` |
| `{CURLSTUB_RESET}` | `CurlHandleStub::resetCurrentInstance();` if CurlStub imported, else empty |
| `{NAMESPACESTUB_RESET}` | `NamespaceStub::reset();` if NamespaceStub imported, else empty |
| `{SINGLETON_RESETS}` | `$this->resetSingleton(ClassName::class);` lines for each detected singleton |
| `{CONFIG_SETUP}` | Configuration `set()` calls if Configuration is a dependency, else empty |
| `{TARGET_CLASS}` | The class name being tested (e.g., `MyNewClass`) |

### Step 5 — Validation

After generating the file:
1. Run `php -l tests/{TargetClass}Test.php` to validate PHP syntax
2. Ensure the file has no warnings from PHP lint
3. Confirm that all detected singletons from Step 3 have reset calls in `setUp()`

### Special Cases

- **Platform tests** (`tests/Platforms/`): Namespace is `Linguise\Script\Core\Platforms`, includes use `dirname(__FILE__) . '/../'` for stubs, and imports `use Linguise\Script\Core\Platforms\{TargetClass};`.

- **Tests for classes with no singletons**: Omit the `resetSingleton` helper method and just call `parent::setUp()`.

- **Tests for classes that need DatabaseHelper**: If the target class has significant DB interaction, include:
  ```php
  include_once(dirname(__FILE__) . '/DatabaseHelper.php');
  use Linguise\Tests\DatabaseHelper;
  ```
  Add `DatabaseHelper::getInstance()` calls in `setUp()` for provisioning.

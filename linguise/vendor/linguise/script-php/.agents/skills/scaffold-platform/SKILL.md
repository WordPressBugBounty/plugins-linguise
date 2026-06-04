---
name: scaffold-platform
description: Generates a new CMS platform adapter class in src/Platforms/ plus a matching test file, following the established PrestaShop/OpenCart/Zencart adapter patterns. Outputs a checklist of remaining manual steps including Plan Mode warnings for Processor.php.
---

# Scaffold Platform

Generates a new CMS platform adapter class + matching test for the Script-PHP project, following the established patterns in `src/Platforms/`.

## Procedure

### Step 1 — Read reference files (read-only, do not modify)



### Step 2 — Get platform name

Ask the user for the platform name (e.g., `Shopify`). Derive:
- **Class name**: `<PlatformName>` (PascalCase)
- **File path**: `src/Platforms/<PlatformName>.php`
- **Namespace**: `Linguise\Script\Core\Platforms`
- **Test file path**: `tests/Platforms/<PlatformName>Test.php`
- **Test namespace**: `Linguise\Script\Core\Platforms`

### Step 3 — Generate the platform adapter class

Create `src/Platforms/<PlatformName>.php` with this structure:

```php
<?php

namespace Linguise\Script\Core\Platforms;

use Linguise\Script\Core\Hook;
use Linguise\Script\Core\Request;
use Linguise\Script\Core\Response;
use Linguise\Script\Core\Helper;
use Linguise\Script\Core\Configuration;
use Linguise\Script\Core\Translation;

defined('LINGUISE_SCRIPT_TRANSLATION') or die(); // @codeCoverageIgnore

class {PLATFORM_NAME}
{
    /**
     * Hook: Called before the translation request is sent to the translation server.
     * Use this to modify the content that will be translated.
     *
     * @param string $content  The HTML content about to be sent for translation
     * @param string $language The target language code
     * @return string          The (optionally modified) content
     */
    public static function onBeforeTranslationRequest($content, $language)
    {
        // TODO: Implement pre-translation content modifications
        return $content;
    }

    /**
     * Hook: Called after the translation response is received.
     * Use this to patch translated content with platform-specific URL rewrites.
     *
     * @param string $content  The translated HTML content
     * @param string $language The target language code
     * @return string          The (optionally modified) translated content
     */
    public static function onAfterTranslationRequest($content, $language)
    {
        // TODO: Implement post-translation content modifications
        return $content;
    }

    /**
     * Hook: Called before POST fields are forwarded to the origin server.
     * Use this to translate search terms or modify request data.
     *
     * @param array $postFields The POST fields being forwarded
     * @return array            The (optionally modified) POST fields
     */
    public static function onBeforePostFields($postFields)
    {
        // TODO: Implement POST field modifications
        return $postFields;
    }
}
```

Read these existing platform adapters to understand the current conventions:
1. `src/Platforms/PrestaShop.php` — most complex adapter with Hook registrations + JsonWalker
2. `src/Platforms/OpenCart.php` — search URL rewriting adapter
3. `src/Platforms/Zencart.php` — JavaScript popup URL patching adapter
4. `tests/Platforms/PrestaShopTest.php` — platform test pattern for reference

Also read `memory-bank/cmsSupport.md` to understand where to add the placeholder section.



### Step 4 — Generate the platform test file

Create `tests/Platforms/<PlatformName>Test.php` following the PrestaShopTest pattern:

```php
<?php declare(strict_types=1);

namespace Linguise\Script\Core\Platforms;

use Linguise\Script\Core\Configuration;
use Linguise\Script\Core\NamespaceStub;
use Linguise\Script\Core\Platforms\{PLATFORM_NAME};
use Linguise\Script\Core\Request;
use Linguise\Script\Core\Response;
use PHPUnit\Framework\TestCase;

include_once(dirname(__FILE__) . '/../NamespaceStub.php');

/**
 * @covers \Linguise\Script\Core\Platforms\{PLATFORM_NAME}
 */
final class {PLATFORM_NAME}Test extends TestCase
{
    private array $serverBackup = [];
    private array $getBackup = [];
    private $originalBaseDir;
    private $originalCms;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!defined('LINGUISE_SCRIPT_TRANSLATION')) define('LINGUISE_SCRIPT_TRANSLATION', true);
        if (!defined('LINGUISE_SCRIPT_TRANSLATION_VERSION')) define('LINGUISE_SCRIPT_TRANSLATION_VERSION', 'test');

        include_once(dirname(__FILE__) . '/../Helper.php');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('LINGUISE_SCRIPT_TESTING')) {
            define('LINGUISE_SCRIPT_TESTING', true);
        }

        NamespaceStub::reset();

        $this->serverBackup = $_SERVER;
        $this->getBackup = $_GET ?? [];

        $configuration = Configuration::getInstance();
        $this->originalBaseDir = $configuration->get('base_dir');
        $this->originalCms = $configuration->get('cms');
        $configuration->set('base_dir', '/var/www/html');
        $configuration->set('cms', '{platform-lowercase}');

        $this->resetRequestSingleton();
        $this->resetResponseSingleton();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_GET = $this->getBackup;

        $configuration = Configuration::getInstance();
        $configuration->set('base_dir', $this->originalBaseDir);
        $configuration->set('cms', $this->originalCms);

        $this->resetRequestSingleton();
        $this->resetResponseSingleton();

        parent::tearDown();
    }

    private function resetRequestSingleton(): void
    {
        $reflection = new \ReflectionClass(Request::class);
        $instanceProperty = $reflection->getProperty('_instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null);
    }

    private function resetResponseSingleton(): void
    {
        $reflection = new \ReflectionClass(Response::class);
        $instanceProperty = $reflection->getProperty('_instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null);
    }

    // TODO: Add test methods here
    public function testOnBeforeTranslationRequestReturnsContent(): void
    {
        $content = '<html><body>Test</body></html>';
        $result = {PLATFORM_NAME}::onBeforeTranslationRequest($content, 'fr-fr');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testOnAfterTranslationRequestReturnsContent(): void
    {
        $content = '<html><body>Translated</body></html>';
        $result = {PLATFORM_NAME}::onAfterTranslationRequest($content, 'fr-fr');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
```

Replace `{PLATFORM_NAME}` with the PascalCase platform name and `{platform-lowercase}` with the lowercase version.


### Step 5 — Update memory-bank/cmsSupport.md

Append a placeholder section to `memory-bank/cmsSupport.md` under the "## Platform Adapters" section (after the Zencart entry). Use this template:

```markdown
### {PlatformName} (`src/Platforms/{PlatformName}.php`)

**Status: STUB — implementation pending**

TODO: Describe what this platform adapter does, which hooks it registers, and any special behavior.

#### Key Hooks

| Hook | Purpose |
|------|---------|
| `onBeforeTranslationRequest` | TODO |
| `onAfterTranslationRequest` | TODO |
| `onBeforePostFields` | TODO |

#### Filesystem Markers (CmsDetect)

| Marker file | Marker content | CMS detected |
|-------------|----------------|--------------|
| TODO | TODO | `{platform-lowercase}` |
```

### Step 6 — Output the manual checklist

After generating the files, output this checklist to the user:

> **Remaining Manual Steps for {PlatformName} Platform**
>
> 1. ⚠ **PLAN MODE REQUIRED** — Add a loader branch in `src/Processor.php::run()`:
>    ```php
>    if (Configuration::get('cms') === '{platform-lowercase}') {
>        // Load and register hooks
>        Hook::add('onBeforeTranslationRequest', {PlatformName}::class);
>        Hook::add('onAfterTranslationRequest', {PlatformName}::class);
>        Hook::add('onBeforePostFields', {PlatformName}::class);
>    }
>    ```
>
> 2. **Update `src/CmsDetect.php`** — Add filesystem marker detection:
>    - Define the marker file(s) and/or content signatures
>    - Add a detection branch in `CmsDetect::detect()` that returns `'{platform-lowercase}'`
>
> 3. **Implement hook callbacks** — Replace the TODO stubs in `src/Platforms/{PlatformName}.php` with actual translation/rewriting logic
>
> 4. **Implement test methods** — Replace the TODO stubs in `tests/Platforms/{PlatformName}Test.php` with real assertions
>
> 5. **Run tests**: `./vendor/bin/phpunit tests/Platforms/{PlatformName}Test.php`


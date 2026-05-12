# Script-PHP — CMS Support

## CMS Detection

**File:** `src/CmsDetect.php`

If `Configuration::$cms` is not `'auto'`, the configured value is returned directly.

When set to `'auto'`, `CmsDetect::detect()` probes the filesystem (from `LINGUISE_BASE_DIR` upward) for known marker files:

| Marker file | Marker content | CMS detected |
|-------------|----------------|--------------|
| `wp-config.php` | _(file exists)_ | `wordpress` |
| `configuration.php` | contains `JConfig` | `joomla` |
| `config.php` | contains `DIR_OPENCART` | `opencart` |
| `config/defines.inc.php` | contains `_PS_ROOT_DIR_` | `prestashop` |
| `mage` | contains `Magento` | `magento` (1.x) |
| `bin/magento` | _(file exists in root or parent of `pub/`)_ | `magento` (2.x) |
| `includes/version.php` | contains `Zen Cart` | `zencart` |
| _(none matched)_ | — | Value of `Configuration::$cms` as-is (e.g., `laravel`) |

## CMS-Specific URL Parsing

**File:** `src/Request.php`

The base URL (the prefix before the language segment) is computed differently per CMS:

| CMS | Base URL source |
|-----|----------------|
| `wordpress` | `home_url()` if available, else doc-root-relative path |
| `joomla` | `JUri::root()` if available |
| `magento`, `laravel` | Empty string (language segment placed at root) |
| Others | Computed relative to doc root from `$_SERVER['DOCUMENT_ROOT']` |

## CMS-Specific Database Credentials

**File:** `src/Database.php`

When the driver is MySQL, credentials are sourced from:

| CMS | Source |
|-----|--------|
| `wordpress` | Parsed from `wp-config.php` (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, table prefix) |
| `joomla` | Read from `JConfig` object (`host`, `db`, `user`, `password`, `dbprefix`) |
| Others | Taken from `Configuration` properties directly |

## Platform Adapters

Platform adapters hook into the request pipeline via `Hook` callbacks registered during `Processor::run()`. They are only loaded when the detected CMS matches.

### PrestaShop (`src/Platforms/PrestaShop.php`)

PrestaShop uses AJAX JSON endpoints for product search, faceted filtering, and pagination. These responses must also be translated but are not HTML pages — they are JSON with embedded HTML fragments.

**How it works:**

1. `onBeforePostFields` — when a search POST is detected, the search term is extracted from POST data and translated before forwarding to the origin server
2. `onBeforeTranslationRequest` — `JsonWalker::wrap()` traverses the JSON response and wraps translatable string values in `<linguise-fragment>` tags, producing a synthetic HTML document that the translation server can process
3. After translation, `JsonWalker::unwrap()` re-extracts the translated values and reconstructs the original JSON structure

**Autocomplete matchers** (`$autocomplete_matchers`) — a declarative array defining which JSON keys should be treated as translatable:

| Matcher type | Example pattern | Behaviour |
|-------------|-----------------|-----------|
| `exact` | `"name"` | Translate exact key by name |
| `regex` | `"/^description/"` | Translate keys matching pattern |
| `link` | `"url"` | Rewrite URL to translated equivalent |

The matcher list is also accessible from `matchers.php` at the repo root (shared definition).

### OpenCart (`src/Platforms/OpenCart.php`)

OpenCart generates search result URLs with query parameters (`?route=product/search&search=<term>`). After the search term in HTML is translated, the associated URLs must also be updated to reflect the translated term.

`getOpenCartReplacement()` builds regex/replacement pairs that inject the language path segment into search URLs. `openCartReplaceHTMLSearchParams()` applies these replacements to the translated HTML content.

### Zencart (`src/Platforms/Zencart.php`)

Zencart uses `javascript:popupWindow('/index.php?...')` inline event handlers for product popups. After translation, the paths inside these JavaScript strings need to be rewritten to include the language segment (`/fr-fr/index.php`).

`onAfterTranslationRequest` scans the translated HTML for these patterns and injects the correct language prefix.

## JsonWalker (`src/JsonWalker.php`)

Used exclusively by the PrestaShop adapter. It:

1. Recursively walks a decoded JSON structure
2. Identifies values matching the declared matchers
3. Wraps them in `<linguise-fragment data-key="...">value</linguise-fragment>` inside a synthetic HTML document
4. After translation returns the synthetic HTML, extracts the translated fragment contents
5. Re-inserts them at the correct JSON paths in the original structure

This allows the standard HTML translation pipeline to handle JSON API responses without any changes to the translation server.

## Adding a New Platform Adapter

1. Create `src/Platforms/MyPlatform.php` with namespace `Linguise\Script\Core\Platforms`
2. Register hook callbacks (`onBeforeTranslationRequest`, `onAfterTranslationRequest`, etc.) using the `Hook` class
3. Load the adapter in `Processor::run()` when `Configuration::get('cms') === 'myplatform'`
4. Create corresponding test `tests/Platforms/MyPlatformTest.php`
5. Update `CmsDetect.php` if the CMS requires new filesystem marker detection
6. Update `memory-bank/cmsSupport.md` with the new adapter documentation

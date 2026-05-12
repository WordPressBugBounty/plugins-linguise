# Script-PHP — Project Brief

## What Is This?

Script-PHP is the **PHP translation proxy script** for the Linguise translation system. It acts as the client-side interface between a website and Linguise's backend services:

- **API Server** (`api.linguise.com`) — provides domain configuration, subscription status, and translation settings via the site token
- **Core / Translation Server** (`translate.linguise.com`) — receives the page HTML and returns translated content

Script-PHP is dropped into any PHP-based website as a `linguise/` directory. It intercepts requests for translated URLs, fetches the original page from the origin server, sends the HTML to the translation server, and returns the localized page to the visitor.

**Important**: This project is NOT the translation engine itself. It is the proxy + configuration layer that delivers pages to users.

## Reuse in CMS Plugins

This project is also used as the **shared core** of the Linguise WordPress and Joomla plugins. Those plugins vendor or reference the `src/` classes directly and call `Processor::run()` as their own entry point. Any public API change in `Processor`, `CurlRequest`, or `Translation` must be coordinated with both plugins.

## Key Responsibilities

- Detect the installed CMS and adjust URL parsing accordingly
- Proxy the origin page request via `CurlRequest`
- Send the HTML to the Linguise translation server via `Translation`
- Cache translated responses on disk via `Cache`
- Serve a management dashboard and first-run OOBE wizard (`?linguise_language=zz-zz`)
- Support self-updates via `Updater`
- Handle CMS-specific quirks via platform adapters (PrestaShop, OpenCart, Zencart)

## Commands

### PHP / Composer

```bash
./vendor/bin/phpunit                        # Run all tests
./vendor/bin/phpunit --testsuite root       # Run only root test suite
./vendor/bin/phpunit --testsuite platforms  # Run only platform adapter tests
./vendor/bin/phpunit tests/ProcessorTest.php  # Run a single test file
composer install                            # Install PHP dependencies
composer dump-autoload                      # Regenerate autoloader
```

### Node / Webpack (Admin UI)

```bash
npm install          # Install frontend dependencies
npm run build        # Build admin + login bundles (production)
npm run dev          # Build in watch mode (development)
```

## Project Version

The current version is stored as the `LINGUISE_SCRIPT_TRANSLATION_VERSION` constant in `linguise.php`. It is checked by the `Updater` when polling for new releases.

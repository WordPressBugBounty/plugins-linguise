# Script-PHP — Project Structure

## Root

```
linguise.php               # Public HTTP entry point; bootstraps and dispatches requests
Configuration.php          # User-editable config (token, CMS, cache, DB credentials)
ConfigurationLocal.php     # Optional local/dev override for Configuration.php
ui-config.php              # Generated at runtime; holds OOBE state + login secret + DB defines
                           #   ⚠ Never edit manually
index.php / index.html     # Directory listing blockers
composer.json              # PHP dependency manifest
package.json               # Node/Webpack dependency manifest
webpack.config.js          # Frontend build config (admin + login bundles)
phpunit.xml                # PHPUnit 9 test configuration
serial.php                 # Utility: reads/writes serialized PHP config
ui-config.php              # Wrote by OobeManager (LINGUISE_OOBE_DONE, LINGUISE_LOGIN_SECRET, …)
play.php                   # Local scratch/playground file (not part of production)
complete_test.php          # Integration test helper
matchers.php               # PrestaShop matcher definitions (shared with PrestaShop adapter)
```

## src/ — Core Classes

```
src/
├── AfterUpdate.php        # Post-update hook placeholder; runs after Updater completes
├── Boundary.php           # Builds multipart/form-data bodies for the translation server
├── Cache.php              # File-based translation cache with eviction and deferred writes
├── Certificates.php       # Downloads + caches CA cert bundle (cacert.pem) for SSL
├── CmsDetect.php          # Filesystem-based CMS auto-detection
├── Configuration.php      # Internal runtime configuration singleton (all settings)
├── CurlMulti.php          # curl_multi_* wrapper for parallel HTTP requests
├── CurlRequest.php        # Proxy curl to origin server; forwards headers/cookies/POST body
│                          #   ⚠ Plan Mode required — shared public API with WP/Joomla plugins
├── Database.php           # DB facade; selects Mysql or Sqlite driver at runtime
├── Debug.php              # Conditional file logger (verbosity 0–5, IP filtering)
├── Defer.php              # Queues callables to run via register_shutdown_function
├── Helper.php             # Static utilities: IP, data-dir, URL building, language meta, CSRF
├── Hook.php               # Lightweight event system (one handler per named event)
├── HttpResponse.php       # Emits JSON or HTML error/success responses and terminates
├── JsonWalker.php         # Traverses JSON API responses; wraps fragments for translation
├── Management.php         # Admin POST action handler (update-config, remote-update, editor)
│                          #   ⚠ Plan Mode required
├── OobeManager.php        # First-run wizard + admin panel controller
│                          #   ⚠ Plan Mode required
├── Processor.php          # Translation request orchestrator; entry point for WP/Joomla plugins
│                          #   ⚠ Plan Mode required — public API used by external plugins
├── Request.php            # Inbound request parser (URL, language, CMS-aware base dir)
├── Response.php           # Accumulates status/headers/body/cookies; Response::end() flushes
├── Session.php            # Admin PHP session wrapper (auth, CSRF tokens)
├── SetCookie.php          # Parses Set-Cookie header strings into objects
├── Translation.php        # Sends HTML to translate.linguise.com; integrates translated response
│                          #   ⚠ Plan Mode required — shared public API with WP/Joomla plugins
├── Updater.php            # Self-update: fetches version JSON, downloads + extracts zip
│                          #   ⚠ Plan Mode required
├── Url.php                # Translates redirect URLs using DB-stored path mappings
│
├── Databases/
│   ├── Mysql.php          # MySQL driver (mysqli); used for WordPress, Joomla, explicit MySQL
│   └── Sqlite.php         # SQLite driver (SQLite3); default for all other CMS
│                          #   ⚠ Both: Plan Mode required — DDL embedded in code
│
├── Platforms/
│   ├── PrestaShop.php     # PrestaShop adapter: JSON AJAX translation via JsonWalker
│   ├── OpenCart.php       # OpenCart adapter: URL rewriting for search params
│   └── Zencart.php        # Zencart adapter: patches popupWindow() JS calls
│
└── templates/
    ├── header.php                 # HTML <head>; loads admin or login asset bundle
    ├── footer.php                 # Closes HTML document
    ├── login.php                  # Login form (unauthenticated state)
    ├── oobe.php                   # First-run OOBE wizard form
    ├── management.php             # Post-login admin panel wrapper (tab structure)
    ├── management-expert.php      # Expert/advanced admin panel view
    ├── editor.php                 # Live editor template
    ├── editor.html                # Static editor HTML shell
    ├── stubs.php                  # Defines __() i18n stub outside WP/Joomla context
    ├── Helper.php                 # Linguise\Script\Core\Templates\Helper; formats error records
    └── tpl/
        ├── main-settings.php      # "Main Settings" tab partial
        ├── advanced.php           # "Advanced" tab partial
        ├── help.php               # "Help" tab partial
        └── footer.php             # Footer partial within management tabs
```

## tests/ — Test Suite

```
tests/
├── BoundaryTest.php
├── CacheTest.php
├── CertificatesTest.php
├── CmsDetectTest.php
├── ConfigurationTest.php
├── CurlMultiTest.php
├── CurlRequestTest.php
├── DatabaseTest.php
├── DebugTest.php
├── DeferTest.php
├── HelperTest.php
├── HookTest.php
├── HttpResponseTest.php
├── JsonWalkerTest.php
├── ManagementTest.php
├── OobeManagerTest.php
├── ProcessorTest.php
├── RequestTest.php
├── ResponseTest.php
├── SessionTest.php
├── SetCookieTest.php
├── TranslationTest.php
├── UrlTest.php
│
├── Databases/
│   ├── MysqlTest.php
│   └── SqliteTest.php
│
├── Platforms/
│   ├── PrestaShopTest.php
│   ├── OpenCartTest.php
│   └── ZencartTest.php
│
├── CurlStub.php           # Stub for mocking curl calls in tests
├── DatabaseHelper.php     # Helper for DB setup/teardown in tests
├── Helper.php             # General test helper utilities
├── NamespaceStub.php      # Stubs for namespaced functions (headers, cookies, etc.)
│
├── data/                  # Fixture files (HTML, JSON, PHP snippets) used by tests
├── templates/             # Template stubs for management UI tests
└── reports/               # PHPUnit coverage output (HTML + Clover XML)
```

## assets/ — Frontend Build Artifacts

```
assets/
├── languages.json               # All supported language codes, names, and flag metadata
├── css/
│   ├── admin.bundle.css         # Built admin styles
│   ├── login.bundle.css         # Built login styles
│   └── *.scss                   # SCSS source files (admin-main, login-main, common, utils, …)
├── js/
│   ├── admin.bundle.js          # Built admin JS (Webpack entry: assets/js/admin.js)
│   ├── login.bundle.js          # Built login JS (Webpack entry: assets/js/login.js)
│   ├── admin.js / login.js      # JS entry points
│   └── vendor/                  # Pre-built vendor files (iris.min.js, jquery-chosen-sortable, …)
├── fonts/                       # Web fonts (Material Icons, etc.)
└── images/                      # Flag images referenced by the admin UI
    ├── flags-rounded/
    └── flags-rectangular/
```

## memory-bank/

Documentation for AI agents and developers. See [AGENTS.md](../AGENTS.md) for the full index.

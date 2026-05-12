# Script-PHP — Architecture

## Request Pipeline

```
Browser → /?linguise_language=fr-fr
    │
    ▼
linguise.php  (public entry point)
    ├── define('LINGUISE_SCRIPT_TRANSLATION', ...)   ← access guard
    ├── require src/Configuration.php singleton
    ├── load Configuration.php (user config) + ConfigurationLocal.php (optional)
    ├── CmsDetect::detect()    ← auto-detect WordPress / Joomla / PrestaShop / etc.
    ├── load / create ui-config.php  ← holds OOBE state + login secret + DB defines
    │
    ├─ [management: ?linguise_language=zz-zz]
    │       ├── OOBE not done → OobeManager::oobeRun()   renders setup wizard
    │       └── OOBE done     → OobeManager::run()       renders admin panel
    │               └── POST actions dispatched via switch:
    │                   clear-cache | update-config | remote-update | logout | …
    │
    └─ [translation: ?linguise_language=XX-XX]
            └── Processor::run()
                    ├── Hook::trigger('beforeRun')
                    ├── ob_start()
                    ├── CurlRequest::makeRequest()      ← proxies origin server
                    │       └── Response   ← accumulates status, headers, body, cookies
                    ├── Cache::serve()                  ← short-circuit if hit
                    ├── Translation::translate()        ← POST to translate.linguise.com
                    │       ├── Boundary   ← builds multipart/form-data payload
                    │       └── Response   ← overwritten with translated HTML
                    ├── Defer::add(fn → Cache::save())  ← write cache after response sent
                    └── Response::end()                 ← flush headers + body to client
```

## WP / Joomla Plugin Entry Point

The WordPress and Joomla plugins call `Processor::run()` directly, bypassing `linguise.php`. They bootstrap the `Configuration` singleton themselves. This means:

- `Processor`, `CurlRequest`, and `Translation` are **shared public API**
- Any change to their public method signatures requires coordination with both plugins
- See `memory-bank/planModeFiles.md` for edit restrictions

## Component Breakdown

### linguise.php

Single public HTTP entry point. Defines the `LINGUISE_SCRIPT_TRANSLATION` access guard constant, bootstraps autoloading, loads configuration layers, detects the CMS, reads/generates `ui-config.php`, and dispatches the request to either the management UI or `Processor::run()`.

### src/Processor.php

Orchestrator for all translation requests. Sequences: open output buffer → `CurlRequest::makeRequest()` → check 304 → optionally serve cache → call `Translation::translate()` → queue cache write via `Defer` → `Response::end()`. Also handles `update()`, `editor()`, and `clearCache()` sub-actions. **This is the entry point called by the WP and Joomla plugins.**

### src/CurlRequest.php

Performs the proxy curl to the origin server, forwarding all incoming headers, cookies, POST fields (including JSON multipart bodies and file uploads). Correctly re-streams `php://input` for JSON POST bodies (PHP bug #9441 workaround). Response headers and body are written into the `Response` singleton.

### src/Translation.php

Sends the buffered HTML to `translate.linguise.com` using a `Boundary` (multipart/form-data) POST that includes the token, URL, requested language, client IP, user-agent, and optional editor/AI tokens. On success, decodes the JSON response, persists translated URL mappings (via `Defer`), and updates `Response`. On failure, issues a 307 redirect back to the non-translated page.

### src/Configuration.php (singleton)

Internal runtime configuration singleton. All private properties with strict defaults. Values are injected via `set()` after the user-editable `Configuration.php` files are loaded. Properties include: `token`, `cms`, `cache_max_size`, `cache_time_check`, `debug`, DB credentials, server host/port, language lists, etc.

### Configuration.php (root — user-editable)

The file the site owner edits. A class `\Linguise\Script\Configuration` with `public static` properties. Methods prefixed `on` are registered as Hook callbacks. `ConfigurationLocal.php` is an optional override for local/dev environments (same structure).

### src/Request.php

Singleton that parses the inbound request: protocol, hostname, path, query string, trailing slashes, language code from `$_GET['linguise_language']`. Implements CMS-specific base-directory detection for WordPress, Joomla, Laravel, Magento, and others.

### src/Response.php

Singleton that accumulates page content, HTTP status code, headers, redirect URL, content type, and cookies from the origin or translation server. `end()` flushes everything to the client.

### src/Cache.php

File-based translation cache. Files are stored as `{data_dir}/cache/{lang}_{md5(content+url)}.php`, prefixed with `<?php die();` to block direct web access. `serve()` short-circuits the full pipeline on a cache hit. `save()` is queued via `Defer` to run after the response is sent. Evicts oldest files when the folder exceeds `cache_max_size` MB.

### src/Database.php

Facade singleton that selects the concrete driver (`Mysql` or `Sqlite`) at construction time based on the detected CMS and configuration. Reads CMS-native DB credentials from WordPress `wp-config.php` or Joomla `JConfig`. Exposes `saveUrls()`, `removeUrls()`, `getTranslatedUrl()`, `retrieveOtherParam()`, etc.

### src/OobeManager.php

Controls the first-run Out-of-Box Experience: collects password, optional token, and DB credentials; validates the DB connection; writes `ui-config.php`. Post-OOBE, `run()` renders the admin panel. Provides `clearCache()`, `clearDebug()`, `logout()`.

### src/Management.php

Handles admin dashboard POST actions: `updateConfig()`, `remoteUpdate()` (fetches config from API), `editorRun()`. Enforces CSRF verification and session authentication.

### src/Updater.php

Self-update logic: fetches `php_script_update.json` from the Linguise CDN, compares versions, downloads and extracts the zip, runs `AfterUpdate::afterUpdateRun()`.

### src/Helper.php

Static utility class: IP detection, data-dir creation, URL building, language metadata, CSRF token helpers, constant definitions.

### src/Hook.php

Lightweight event system. `add($name, $class)` registers a single handler per event; `trigger($name)` invokes it; `wait($name)` is a blocking variant. Used for `onBeforeMakeRequest`, `onBeforeTranslationRequest`, etc.

### src/Boundary.php

Builds `multipart/form-data` request bodies for curl calls to the translation server.

### src/Certificates.php

Downloads and caches the CA certificate bundle (`cacert.pem`) for SSL verification. Refreshes daily/weekly.

### src/CmsDetect.php

Probes filesystem markers to auto-detect the installed CMS. Falls back to the `cms` config value if `auto` is not set.

### src/JsonWalker.php

Traverses JSON API responses (e.g., PrestaShop AJAX endpoints), wraps translatable values in `<linguise-fragment>` tags for the translation server, and re-extracts resulting translated values. Used by the PrestaShop platform adapter.

### src/Defer.php

Queues PHP callables to run in a `register_shutdown_function`, enabling cache writes and URL mapping persistence to happen after the HTTP response has been sent.

### src/Debug.php

Conditional file-based logger. Writes to `debug.php`. Supports verbosity levels 0–5 and IP-restricted logging.

### src/Session.php

PHP session wrapper for admin authentication. Provides `start()`, `hasSession()`, `generateCsrfToken()`, `verifyCsrfToken()`, `oobeComplete()`.

### src/SetCookie.php

Parses `Set-Cookie` header strings into structured objects (MIT-licensed, ported from Guzzle).

### src/Url.php

Translates redirect URLs by looking up translated path equivalents from the database.

### src/AfterUpdate.php

Hook called after a self-update completes. Currently a no-op placeholder for post-update logic.

## Namespace Structure

All source classes are under `Linguise\Script\Core` (PSR-4, mapped to `src/`). Template helpers are under `Linguise\Script\Core\Templates` (mapped to `src/templates/`). The user-facing `Configuration.php` uses `Linguise\Script`.

## Sharding / Storage

There is no sharded database architecture. Storage is either SQLite (default, file-based) or MySQL (WordPress, Joomla, or explicit config). Two logical tables:

- `urls` — translated URL mapping (source path → translated path per language)
- `other_params` — JSON option blobs (configuration snapshots)

Data files live in `{data_dir}/` which is token-scoped to prevent cross-domain leakage.

# Script-PHP — Active Context

## Last Updated
2026-09-30

## What Was Done
- **2026-06-16**: Migrated build system from Webpack 5 to Vite 8 (Rolldown). Upgraded Node from 18 to 24.14.1. Single multi-entry config (`vite.config.mjs`) for admin + login bundles. jQuery kept as external. CSSCodeSplit disabled for proper CSS extraction. Output stays in `assets/` (same as source).
- **2026-04-06**: Created `memory-bank/` directory and `AGENTS.md` from codebase exploration.
- Added `force_ipv4` config option (Plan Mode approved): root `Configuration.php` + `src/Configuration.php` default `false`; `CurlRequest::makeRequest()` sets `CURLOPT_IPRESOLVE = CURL_IPRESOLVE_V4` only when enabled and the constants exist (PHP 5.5+/curl 7.10.8+)
- Added `ConfigurationTest` cases (default disabled, set/get, `toArray`, loaded from config file) and `CurlRequestTest` cases (option applied when enabled, absent by default)
- Fixed Moodle session/`sesskey` failures in the translation proxy (Plan Mode approved)
- Added `SameSite` cookie preservation in `src/SetCookie.php` and `src/Response.php`
- Guarded `session_write_close()` in `src/CurlRequest.php` to only close the Linguise admin session (`LINGSESSION`)
- Updated `src/CurlRequest.php` to forward raw `application/x-www-form-urlencoded` bodies from `php://input`
- Added/updated tests in `SetCookieTest`, `ResponseTest`, and `CurlRequestTest`
- Added Content-Encoding handling: `CurlRequest` decodes compressed origin bodies (gzip/deflate/br/zstd) before translation and records the encoding; `Response::end()` re-compresses before sending (Plan Mode — CurlRequest/Configuration)
- Added `compress_response` config option (root `Configuration.php` + `src/Configuration.php` default `true`)
- Fixed `CurlHandleStub` to separate request headers from response headers and emit a simulated HTTP status line

## Current State
Root suite: 619 tests, 4 pre-existing failures/errors unrelated to this change (3 `ResponseTest` cases, 1 `SqliteTest` file-lock error) and 1 skipped (mbstring). All platform tests pass (41). No public API signatures changed. Compressed origin responses are decoded for translation and re-compressed with the same `Content-Encoding` before being sent to the browser (configurable via `compress_response`, currently `false` by default). Curl requests can optionally be pinned to IPv4 via `force_ipv4` (default `false` = current behaviour). Origin timeouts default to 5s connect / 15s total; translation-server timeouts default to 20s connect / 300s total and are configurable.

## Key Things to Know
- The WordPress and Joomla plugins call `Processor::run()` directly — it is a shared public API
- `ui-config.php` is generated — never edit manually
- There is no database migration system — schema DDL is embedded in `src/Databases/*.php`
- Any change to public method signatures in `Processor`, `CurlRequest`, or `Translation` requires Plan Mode
- Origin request timeouts use `origin_connect_timeout` and `origin_timeout`; non-positive values fall back to 5s and 15s
- Translation server timeouts use `translation_connect_timeout` and `translation_timeout`; non-positive values fall back to 20s and 300s
- OOBE has two modes: with-token (migration of existing customers) and without-token (new installs)
- The PrestaShop adapter is the most complex — it uses `JsonWalker` for translating JSON AJAX responses
- `CurlRequest::makeRequest()` now preserves the origin CMS session; only `LINGSESSION` is explicitly closed
- `Response::end()` emits `SameSite` cookie attributes on PHP 7.3+
- `CurlRequest::makeRequest()` prefers raw `php://input` for url-encoded POSTs
- `CurlRequest` decodes `Content-Encoding` (gzip/deflate/br/zstd) so translated content is plain text; `Response::end()` re-compresses with the tracked encoding unless `compress_response` is `false`
- `popup_background_color` / `popup_flag_tint_background_color` are single-value RGBA tokens — the opacity input in `renderColorToggleRGBA()` is a scratch control and must never be named `linguise_options[...]`
- iris normalises a colour input to HEX on `_init()` and after every change, so any single-value RGBA input must be re-applied after `$elem.iris()` and again via `setTimeout(..., 0)` inside the `change` callback; `data-colorama-rgba="1"` is the marker the JS keys off

## Next Steps
- Coordinate new timeout keys into vendored WordPress/Joomla releases and customer expert-mode configuration
- Evaluate a WordPress architecture change that removes the same-site origin loopback instead of only bounding it

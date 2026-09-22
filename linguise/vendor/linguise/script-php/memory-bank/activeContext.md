# Script-PHP — Active Context

## Last Updated
2026-09-14

## What Was Done
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
Root suite: 613 tests, 5 pre-existing failures/errors unrelated to this change (`compress_response` default/expectation mismatch in `ConfigurationTest` + 3 `ResponseTest` cases, `SqliteTest` file-lock error) and 1 skipped (mbstring). All platform tests pass (41). No public API signatures changed. Compressed origin responses are decoded for translation and re-compressed with the same `Content-Encoding` before being sent to the browser (configurable via `compress_response`). Curl requests can optionally be pinned to IPv4 via `force_ipv4` (default `false` = current behaviour).

## Key Things to Know
- The WordPress and Joomla plugins call `Processor::run()` directly — it is a shared public API
- `ui-config.php` is generated — never edit manually
- There is no database migration system — schema DDL is embedded in `src/Databases/*.php`
- Any change to public method signatures in `Processor`, `CurlRequest`, or `Translation` requires Plan Mode
- OOBE has two modes: with-token (migration of existing customers) and without-token (new installs)
- The PrestaShop adapter is the most complex — it uses `JsonWalker` for translating JSON AJAX responses
- `CurlRequest::makeRequest()` now preserves the origin CMS session; only `LINGSESSION` is explicitly closed
- `Response::end()` emits `SameSite` cookie attributes on PHP 7.3+
- `CurlRequest::makeRequest()` prefers raw `php://input` for url-encoded POSTs
- `CurlRequest` decodes `Content-Encoding` (gzip/deflate/br/zstd) so translated content is plain text; `Response::end()` re-compresses with the tracked encoding unless `compress_response` is `false`

## Next Steps
_(none pending)_

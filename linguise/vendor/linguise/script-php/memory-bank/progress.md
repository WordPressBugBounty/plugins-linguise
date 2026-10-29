# Script-PHP — Progress

## Active Tasks

_(none)_

## Completed Tasks

| Date | Task | Notes |
|------|------|-------|
| 2026-06-16 | Webpack → Vite 8 migration | Upgraded to Vite 8 (Rolldown), Node 24.14.1. Multi-entry config for admin+login, CSSCodeSplit disabled, jQuery external. |
| 2026-04-06 | Initial memory bank and AGENTS.md created | Bootstrapped from codebase exploration |
| 2026-07-20 | Moodle session/`sesskey` fix (Plan Mode) | Updated CurlRequest, Response, SetCookie; added tests; all root tests pass |
| 2026-08-10 | Compressed response decoding + re-compression (Plan Mode) | CurlRequest decodes gzip/deflate/br/zstd and tracks encoding; Response::end() re-compresses (compress_response config); CurlStub request/response header separation |
| 2026-08-24 | Configurable origin timeouts | Added fail-fast 3s/10s defaults, origin diagnostics, and regression tests |
| 2026-09-02 | Searchable Dropdown configuration integration | Added `dropdown_search`, `panel_border_radius` default/clamping, Item text color labels, API sync/hydration mapping, template controls, focused tests; 32 tests and 87 assertions pass without coverage |
| 2026-09-08 | Searchable Dropdown visibility invariant | Prevented disabling both Flag and language name in admin UI and direct POST updates; Popup item setting now covers Popup/Searchable Dropdown; 34 management tests (91 assertions) and 3 template tests (14 assertions) pass without coverage |
| 2026-09-14 | `force_ipv4` config option (Plan Mode) | New `force_ipv4` bool (default `false`) in root + src `Configuration.php`; `CurlRequest::makeRequest()` sets `CURLOPT_IPRESOLVE = CURL_IPRESOLVE_V4` behind a `defined()` guard; tests added to `ConfigurationTest` + `CurlRequestTest` |
| 2026-09-15 | Set panel radius default to 10px | Updated management persistence, rendered controls, and copied design spec; explicit radius values and zero remain supported |
| 2026-09-23 | Choose dropdown type after account setup | Added distinct-language threshold recommendation, local customization marker/migration, iframe UI refresh, and API-sync exclusion; focused suites and admin build pass; unrelated full-suite failures remain |
| 2026-09-30 | Popup background colour options (Plan Mode: Management, OobeManager) | Added `popup_background_color` (`rgba(24, 14, 80, 0.42)`) and `popup_flag_tint_background_color` (`#f1eefe`) as single RGBA tokens; `Helper::parseColorToRgba()`/`sanitizeCssColor()`, OOBE defaults, API payload keys required by API-JS, `transformToLocalConfig()` passthrough, `renderColorToggleRGBA()` admin control, and iris-safe RGBA handling in `admin.js`; HelperTest 23, ManagementTest 40, OobeManagerTest 50, template tests 25 pass |


## Backlog

_(none)_

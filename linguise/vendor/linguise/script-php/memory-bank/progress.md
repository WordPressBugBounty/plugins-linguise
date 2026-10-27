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
| 2026-09-14 | `force_ipv4` config option (Plan Mode) | New `force_ipv4` bool (default `false`) in root + src `Configuration.php`; `CurlRequest::makeRequest()` sets `CURLOPT_IPRESOLVE = CURL_IPRESOLVE_V4` behind a `defined()` guard; tests added to `ConfigurationTest` + `CurlRequestTest` |

## Backlog

_(none)_

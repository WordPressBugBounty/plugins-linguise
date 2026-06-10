---
name: debug-translation
description: Diagnoses translation failures by reading Configuration.php for token and debug settings, checking certificates/cacert.pem availability, examining debug log entries, and cross-referencing findings against the request pipeline in memory-bank/architecture.md to identify the most likely failure stage (CurlRequest, Cache, or Translation). Read-only — never modifies any file.
---

# Debug Translation

Diagnoses translation failures in Script-PHP by reading the relevant configuration and state files, interpreting their contents, and identifying which stage of the request pipeline is the most likely failure point.

## Procedure

### Step 1 — Read Configuration.php and report token + debug state (read-only)

Read the root `Configuration.php` file and extract these values:

1. **Token**: Read the `public static $token` property.
   - If the value is `'REPLACE_BY_YOUR_TOKEN'` (the placeholder default), report: `TOKEN: Not configured (still set to default placeholder)`
   - If the value is empty (`''`), report: `TOKEN: Empty`
   - Otherwise, report: `TOKEN: Present (value hidden)` — never display the actual token value

2. **Debug level**: Read the `public static $debug` property.
   - If `false`, report: `DEBUG: Disabled (set to false)`
   - If an integer (e.g., `5`), report: `DEBUG: Enabled at verbosity level {value}`

3. **Debug IP restriction**: Check the root `Configuration.php` for a `public static $debug_ip` property assignment (the internal counterpart is `src/Configuration.php` line 38).
   - If the root config has `$debug_ip` set to a non-empty string, report: `DEBUG_IP: Restricted to requests from {ip}`
   - If not set, empty, or the property does not exist in the root config, report: `DEBUG_IP: Not restricted (logging applies to all requests)`

### Step 2 — Check certificates/cacert.pem (read-only)

Check whether `certificates/cacert.pem` exists on disk. The certificates directory is at `{project_root}/certificates/`.

1. Check `file_exists('certificates/cacert.pem')`:
   - If exists: Report the file size and last modified date. Also check `certificates/time.txt` (the last-check timestamp) and report how many hours/days ago the certificates were last validated.
   - If does NOT exist: Report `CERTIFICATES: MISSING — cacert.pem not found. All HTTPS calls to translate.linguise.com or any HTTPS origin server will fail.`

2. Also check the `$dl_certificates` property in the root `Configuration.php`:
   - If `true`: Report `DL_CERTIFICATES: Enabled (auto-download from curl.se)`
   - If `false`: Report `DL_CERTIFICATES: Disabled`

### Step 3 — Read and display debug log entries (read-only)

The debug log can exist in two locations:
- `{project_root}/debug.php` — where `Debug.php` writes (see `Debug::enable()`, line 19)
- `{data_dir}/debug.php` — the token-scoped data directory (per AGENTS.md and environment.md)

Check both locations. For each file that exists:

1. Read the file. The file begins with `<?php die(); ?>` as an access guard — skip this line.
2. Display the last 25 non-empty lines as the most recent log entries.
3. If the file contains no log entries beyond the access guard, report: `DEBUG LOG: Empty (no entries recorded yet)`
4. If neither file exists, report: `DEBUG LOG: Not found — no debug output has been written. Either debug is disabled or no requests have been processed since enabling.`

### Step 4 — Correlate findings against the request pipeline

Read `memory-bank/architecture.md` to reference the current pipeline stages. Cross-reference the collected state against these failure scenarios:

| Pipeline Stage | Symptom in collected state | Indicator |
|---|---|---|
| **CurlRequest** (origin proxy) | Debug log contains curl errors or HTTP non-2xx responses from origin; origin server may be unreachable | Look for curl error codes or non-200 status in debug output |
| **Cache** | Cache is enabled but serving stale/incorrect content; cache files may be corrupted | Check if `$cache_enabled = true` in root `Configuration.php` and debug log mentions cache operations |
| **Translation** (translate.linguise.com) | Debug log contains connection errors to `translate.linguise.com`; token may be invalid | Look for `translate.linguise.com` connection errors or 4xx/5xx responses in debug output; cross-reference with token state from Step 1 |
| **Certificates** | cacert.pem missing or expired; HTTPS connections fail silently | Cross-reference Step 2 findings; if cacert.pem is missing and the origin or translation server uses HTTPS, this is the likely cause |

### Step 5 — Output the diagnostic report

Format the output as a structured report:

```
## Translation Debug Report

### Configuration State
- Token: {TOKEN STATE}
- Debug: {DEBUG STATE}
- Debug IP: {DEBUG_IP STATE}
- Certificates Download: {DL_CERTIFICATES STATE}

### Certificates
- cacert.pem: {EXISTS/MISSING} {SIZE + DATE if exists}
- Last validated: {TIME if time.txt exists, otherwise "Unknown"}

### Debug Log ({file path})
{Last 25 log lines or "Empty" / "Not found"}

### Pipeline Analysis
**Most likely failure stage: {STAGE NAME}**

Evidence:
- {Finding 1 from collected state}
- {Finding 2 from collected state}
- {Finding 3 from collected state}

### Recommended Debugging Steps (from AGENTS.md)
{Referenced step(s) from the "Debugging a translation failure" section}
```

Reference the specific step(s) from the AGENTS.md "Debugging a translation failure" section:

- If CurlRequest is the likely failure → cite Step 4: "Check `CurlRequest` response: is the origin page being fetched correctly?"
- If Translation is the likely failure → cite Step 5: "Check `Translation::translate()`: is the translation server reachable at `translate.linguise.com`?"
- If Cache is suspected → recommend clearing cache and cite Step 1: "Enable debug logging: set `$debug = 5` in `Configuration.php`"
- If token is invalid → cite Step 3: "Verify the token in `Configuration.php` matches the domain in the Linguise dashboard"
- If certificates missing → cite Step 6: "Check `Certificates.php`: is `cacert.pem` present and up to date?"
- If no definitive cause is found → cite Step 1 + Step 2: "Enable debug logging and check `{data_dir}/debug.php` for log output"

### Step 6 — Validation

After running, verify:
1. **No file was modified** — this skill is strictly read-only
2. The root `Configuration.php` was read and token/debug values were extracted
3. `certificates/cacert.pem` was checked for existence
4. Both `debug.php` locations were checked for log entries
5. `memory-bank/architecture.md` was cross-referenced for pipeline stage descriptions
6. The output includes a specific AGENTS.md debugging step reference

### Edge Cases

- **`Configuration.php` not found**: Report `ERROR: Configuration.php not found at project root. Cannot perform translation debug diagnostic.`
- **`certificates/` directory not found**: Report `CERTIFICATES: Directory missing — no certificates/cacert.pem can exist.`
- **Debug log contains sensitive data**: The skill reads and displays log lines as-is. Warn: `Note: Debug log output may contain sensitive information.`
- **Multiple failure indicators**: If the collected state points to multiple possible failure stages, list all of them ordered by likelihood, with the most likely stage first.
- **`memory-bank/architecture.md` not found**: Still perform the diagnostic, but note: `WARNING: memory-bank/architecture.md not found — pipeline cross-reference unavailable.`
- **`AGENTS.md` not found**: Still perform the diagnostic, but note: `WARNING: AGENTS.md not found — debugging steps reference unavailable.`
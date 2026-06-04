---
name: plan-mode-preflight
description: Checks whether any target file path is in the Plan Mode required list from memory-bank/planModeFiles.md. If a match is found, outputs a BLOCKED status with the file category and reason, references the Plan Mode workflow from AGENTS.md, and halts. This skill is read-only — it never modifies any file.
---

# Plan Mode Preflight

Safety gate that intercepts edits to Plan Mode files before they are applied. Runs as a pre-check whenever the caller intends to modify one or more source files.

## Procedure

### Step 1 — Read the Plan Mode file list (read-only)

Read `memory-bank/planModeFiles.md`. Extract every file path from the "Files Requiring Plan Mode" table along with its category and reason.

The current Plan Mode file list (13 entries) consists of:

| File | Category | Reason |
|---|---|---|
| `src/Processor.php` | Plugin API | Entry point called directly by WordPress and Joomla plugins. |
| `src/CurlRequest.php` | Plugin API | Core origin proxy used by all integrations. |
| `src/Translation.php` | Plugin API | Sends HTML to the translation server; shared with WP/Joomla. |
| `src/Configuration.php` | Core Schema | Internal configuration singleton every class depends on. |
| `Configuration.php` | User Config | The file site owners edit; changing properties breaks installations. |
| `src/OobeManager.php` | Setup Flow | Generates and rewrites `ui-config.php`; errors break first-run setup. |
| `src/Management.php` | Admin Security | Handles CSRF verification and all admin POST actions. |
| `src/Database.php` | Data Layer | DB facade with DDL embedded in code; no migration system. |
| `src/Databases/Mysql.php` | Data Layer | MySQL driver with in-code DDL. |
| `src/Databases/Sqlite.php` | Data Layer | SQLite driver with in-code DDL. |
| `src/Updater.php` | Self-Update | Fetches remote zip and replaces files on disk. |
| `src/Certificates.php` | SSL Security | Manages CA cert bundle for all HTTPS calls. |
| `ui-config.php` | Generated File | Written by OobeManager; never edit manually. |

**Important**: Always re-read `memory-bank/planModeFiles.md` at runtime — the file list may have been updated since this skill was written. The table above is a reference snapshot. The authoritative list is always the file itself.

### Step 2 — Read AGENTS.md for Plan Mode workflow reference (read-only)

Read `AGENTS.md` and locate the section describing the Plan Mode workflow steps. Extract the key steps:

1. Document the proposed change (what file, what method/property, what the new signature/behavior is)
2. Explain the impact (which callers are affected: WP plugin, Joomla plugin, tests, other `src/` classes)
3. List migration steps if a DB schema change is involved
4. Get user approval before writing any code
5. Update tests — all Plan Mode changes require updated or new tests before PR is mergeable

Also note the additional restrictions from `planModeFiles.md`:
- Changing any public method signature in `Processor`, `CurlRequest`, or `Translation`
- Adding or removing columns in SQLite or MySQL tables
- Adding a new `on*` hook to `Configuration.php`

### Step 3 — Accept file path arguments

Take one or more file paths as input. Each path should be a relative path from the project root (e.g., `src/Processor.php`, `Configuration.php`, `src/Management.php`).

Normalize paths:
- Strip leading `./` if present
- Convert backslashes to forward slashes for cross-platform consistency
- Match against the Plan Mode list using case-insensitive comparison (file paths on Windows may vary in case)

### Step 4 — Check each file path against the Plan Mode list

For each input file path:

1. Compare against every entry in the Plan Mode file list
2. Use exact relative path matching (the Plan Mode list uses repository-root-relative paths)

**Matching rules**:
- `src/Processor.php` matches `src/Processor.php` exactly
- `./src/Processor.php` matches after stripping `./`
- `src\Processor.php` matches after normalizing backslashes
- Partial matches (e.g., `Processor.php` alone without the `src/` prefix) should be treated as **no match** — the full relative path must match an entry in the list

### Step 5 — Determine BLOCKED vs. CLEAR

#### If at least one file path matches → BLOCKED

For **each matched file**, output:

```
BLOCKED: {file_path}
  Category: {category from planModeFiles.md}
  Reason: {reason from planModeFiles.md}
```

After listing all BLOCKED files, output the Plan Mode workflow reference and **halt immediately**:

```
═══════════════════════════════════════════════════
PLAN MODE REQUIRED
═══════════════════════════════════════════════════

The following files require Plan Mode before any modification:

{list of blocked files with categories}

Plan Mode Workflow (from AGENTS.md):
  1. Document the proposed change (file, method/property, new signature/behavior)
  2. Explain the impact (WP plugin, Joomla plugin, tests, other src/ classes)
  3. List migration steps if a DB schema change is involved
  4. Get user approval before writing any code
  5. Update tests before the PR is mergeable

Additional restrictions:
  - Public method signatures in Processor, CurlRequest, Translation: even
    adding a default parameter requires coordination with WP/Joomla plugins
  - DB schema changes: no migration system exists — manual DDL coordination needed
  - New on* hooks in Configuration.php: must be documented for plugin maintainers

Create a plan first before modifying any of these files.
═══════════════════════════════════════════════════
```

**Halt immediately** — do not proceed with any further action, file reads, or file writes.

#### If no file path matches → CLEAR

Output:

```
CLEAR: All files checked — no Plan Mode files detected.

Files checked:
  - {file_path_1} — not in Plan Mode list
  - {file_path_2} — not in Plan Mode list
  - ...

Safe to proceed with modifications.
```

### Step 6 — Special case: file not found or ambiguous

If a provided file path does not match any entry in the Plan Mode list **and** does not appear to be a valid file path in the project:

```
UNKNOWN: {file_path}
  The file does not match any Plan Mode entry but could not be verified as a valid project file.
  Proceed with caution — verify the file path is correct.
```

For files that are clearly not in the Plan Mode list (e.g., `src/Helper.php`, `src/Hook.php`), simply mark them as CLEAR.

### Step 7 — Validation

After running, verify:

1. **No file was modified** — this skill is strictly read-only. If any file write was attempted, the skill is misconfigured.
2. The Plan Mode file list was read from `memory-bank/planModeFiles.md`, not hardcoded (the hardcoded table in Step 1 is a reference snapshot only).
3. AGENTS.md was read for the Plan Mode workflow steps to include in BLOCKED output.
4. All matched files have their category and reason displayed.
5. The output clearly distinguishes BLOCKED (halt) from CLEAR (proceed).

### Edge Cases

- **No file paths provided**: Output `No file paths specified. Provide at least one file path to check.`
- **Empty Plan Mode list** (`planModeFiles.md` has no entries): Treat all files as CLEAR. Output a warning: `Plan Mode list is empty — all files cleared by default. Verify planModeFiles.md is correctly populated.`
- **`planModeFiles.md` is missing**: Output `ERROR: memory-bank/planModeFiles.md not found. Cannot perform Plan Mode preflight check. Verify the memory-bank directory is intact.`
- **`AGENTS.md` is missing**: Still perform the check (the Plan Mode file list is the primary data source), but note: `WARNING: AGENTS.md not found — Plan Mode workflow reference unavailable.`
- **Duplicate file paths in input**: Deduplicate before checking — report each file only once.

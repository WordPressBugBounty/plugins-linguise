---
name: update-memory-bank
description: Reads all memory-bank files, identifies which ones need updating based on the described change, updates activeContext.md and progress.md for every invocation, and applies targeted edits to other relevant files or outputs a manual-review checklist. Never auto-modifies planModeFiles.md — outputs a Plan Mode warning instead.
---

# Update Memory Bank

Keeps the Script-PHP `memory-bank/` documentation synchronized after non-trivial changes: platform adapter work, pipeline modifications, admin actions, dependency updates, or file structure changes.

## Procedure

### Step 1 — Read all memory-bank files (read-only, do not modify)

Read these 11 files to establish their current state and structure:

1. `memory-bank/activeContext.md` — always updated
2. `memory-bank/architecture.md` — pipeline and component descriptions
3. `memory-bank/cmsSupport.md` — CMS detection, platform adapter docs
4. `memory-bank/environment.md` — constants, config layers, data directory
5. `memory-bank/managementFlow.md` — admin panel, OOBE flow, admin POST actions table
6. `memory-bank/planModeFiles.md` — ⚠ Plan Mode file list (never auto-modify)
7. `memory-bank/progress.md` — always updated (Completed Tasks table)
8. `memory-bank/projectBrief.md` — project overview (rarely changes)
9. `memory-bank/structure.md` — annotated file tree for all directories
10. `memory-bank/techStack.md` — PHP/Composer/Node/Webpack versions and deps
11. `memory-bank/testing.md` — PHPUnit config, test patterns, best practices

### Step 2 — Get the change description

Ask the user for:

- **Change type**: What kind of work was done (`platform-adapter`, `pipeline`, `admin-action`, `dependency`, `config`, `new-file`, `test-infra`, or `general`)
- **Change summary**: One-paragraph description of what was done (used in `activeContext.md` and `progress.md`)
- **Additional context** (optional): Any specific details needed for precision (e.g., the platform name, action name, file paths, dependency version)

### Step 3 — Identify relevant memory-bank files

Use this trigger mapping to determine which files beyond `activeContext.md` and `progress.md` need updating:

| Change type | Files to update | What to update |
|---|---|---|
| `platform-adapter` | `cmsSupport.md`, `structure.md` | New adapter section in Platform Adapters table; new file in `src/Platforms/` tree; updated `tests/Platforms/` tree |
| `pipeline` | `architecture.md` | Update the request pipeline diagram or component breakdown section |
| `admin-action` | `managementFlow.md`, `structure.md` | New row in Admin POST Actions table; new file in `src/templates/tpl/` tree |
| `dependency` | `techStack.md` | Update version numbers or add/remove rows in dependency tables |
| `config` | `environment.md` | Update constant/property definitions or config layer descriptions |
| `new-file` | `structure.md` | Add new file entries in the annotated file tree |
| `test-infra` | `testing.md` | Update test helpers, patterns, or fixture descriptions |
| `plan-mode-list` | `planModeFiles.md` | ⚠ **NEVER auto-modify** — output a Plan Mode warning instead (see Step 6) |
| `general` | _(none beyond defaults)_ | Only `activeContext.md` + `progress.md` are updated |

If the change spans multiple types (e.g., adding a platform adapter also adds new files), include all matching files.

### Step 4 — Update activeContext.md (always)

`memory-bank/activeContext.md` is updated for **every invocation**. Apply these edits:

#### 4a. Update the date

Replace the date line under `## Last Updated` with today's date in `YYYY-MM-DD` format:

```markdown
## Last Updated
{YYYY-MM-DD}
```

#### 4b. Update "What Was Done"

Replace or append to the `## What Was Done` section with a bullet list summarizing the change:

```markdown
## What Was Done
- {Change summary bullet 1}
- {Change summary bullet 2 (if needed)}
```

If there are existing bullets that are still relevant, keep them. Remove stale bullets that no longer reflect the current state.

#### 4c. Update "Current State"

Replace the `## Current State` section with a concise description of the post-change state:

```markdown
## Current State
{One-paragraph description of the resulting state after the change.}
```

#### 4d. Review "Key Things to Know" and "Next Steps"

- **Key Things to Know**: Only update if the change introduces new critical constraints or removes obsolete ones. Otherwise leave unchanged.
- **Next Steps**: Update to reflect any pending work. Set to `_(none pending)_` if nothing remains.

### Step 5 — Update progress.md (always)

`memory-bank/progress.md` is updated for **every invocation**. Append a new row to the `## Completed Tasks` table:

```markdown
| {YYYY-MM-DD} | {Change summary (short)} | {Optional notes} |
```

If the task was previously listed under `## Active Tasks` or `## Backlog`, remove it from those sections.

### Step 6 — Update other identified files

For each file identified in Step 3 (beyond the always-updated ones), determine whether the skill can apply a precise edit or must output a manual-review instruction.

#### 6a. Files with predictable edit targets

| File | Edit approach |
|---|---|
| `cmsSupport.md` | Append a new `### PlatformName` subsection under `## Platform Adapters` following the existing PrestaShop/OpenCart/Zencart pattern. If adding markers to the CMS Detection table, append a row to the `## CMS Detection` table. |
| `managementFlow.md` | Append a new row to the Admin POST Actions table. |
| `structure.md` | Add new file entries in the correct directory section of the annotated tree. |
| `techStack.md` | Update version numbers in existing rows, or append new rows to the relevant table. |
| `environment.md` | Add new constant/property rows to the relevant table, or update values in existing rows. |
| `testing.md` | Add new test file entries to the Test File Map table, or update Test Helpers if new helpers were added. |
| `architecture.md` | Update the specific component's description in `## Component Breakdown`. If the request pipeline changed, update the ASCII diagram in `## Request Pipeline`. |

For each targeted edit:
1. Read the current content of the section to ensure the edit will be precise
2. Apply the edit using the established patterns and table formats from that file
3. Do NOT rewrite entire files — only the affected sections

#### 6b. planModeFiles.md — NEVER auto-modify

If the change involves modifying the Plan Mode file list, do NOT edit `memory-bank/planModeFiles.md`. Instead, output:

> ⚠ **PLAN MODE WARNING**: The Plan Mode file list in `memory-bank/planModeFiles.md` may need updating. This file affects the agent safety workflow and must be manually reviewed. Proposed changes:
> - {List specific proposed additions/removals}
>
> Please review and manually update `memory-bank/planModeFiles.md` if the Plan Mode file list has changed.

### Step 7 — Output the final report

After completing all updates, output a summary:

```
## Memory Bank Update — Complete

### Files Modified
- memory-bank/activeContext.md — updated date, summary, and current state
- memory-bank/progress.md — appended completed task
- memory-bank/{file3}.md — {brief description of what was edited}
...

### Files Requiring Manual Review
- memory-bank/{file}.md — {section name}: {reason manual review is needed}

### Files Not Modified
- memory-bank/{file}.md — {reason not modified}
...
```

If `planModeFiles.md` was identified as potentially needing changes, include it under "Manual Review" with the Plan Mode warning from Step 6b.

### Step 8 — Validation

After all file edits are complete, verify:

1. Each modified file parses as valid markdown — check that tables, lists, and headings are properly aligned
2. `activeContext.md` has today's date and a non-empty summary
3. `progress.md` has the new task entry with today's date
4. No file was overwritten in its entirety — only targeted sections changed
5. All table formats match the existing conventions in each file (column alignment, markdown syntax)

### Special Cases

- **First invocation on a fresh day**: If `activeContext.md` already has today's date (from a previous run), append to the existing "What Was Done" list rather than replacing it.
- **No change type matches**: Default to `general` — update only `activeContext.md` and `progress.md`.
- **Change involves both a platform adapter and an admin action**: Apply both `platform-adapter` and `admin-action` trigger mappings.
- **Change adds a new memory-bank file itself**: Update `structure.md` and `projectBrief.md` to reference the new file.

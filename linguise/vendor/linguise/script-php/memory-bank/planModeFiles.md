# Script-PHP — Plan Mode Files

Certain files are critical to the correctness and stability of Script-PHP and its dependent CMS plugins (WordPress, Joomla). Before modifying any file listed here, you **must enter Plan Mode**: document the proposed change, explain the impact, and get user approval before touching the file.

## Files Requiring Plan Mode

| File | Category | Reason |
|------|----------|--------|
| `src/Processor.php` | **Plugin API** | Entry point called directly by the WordPress and Joomla plugins. Any change to its public method signatures (`run()`, `update()`, `editor()`, `clearCache()`) breaks external integrations. |
| `src/CurlRequest.php` | **Plugin API** | Core origin proxy used by all integrations. Its public request/response contract is depended on by WP, Joomla, and directly by tests. |
| `src/Translation.php` | **Plugin API** | Sends HTML to the translation server. Its public API (`translate()`, return format) is shared with WP/Joomla plugins. |
| `src/Configuration.php` | **Core Schema** | Internal configuration singleton that every class depends on. Adding, removing, or renaming a property affects all callers. |
| `Configuration.php` (root) | **User Config** | The file site owners edit. Changing its property names or `on*` method signatures breaks existing installations. |
| `src/OobeManager.php` | **Setup Flow** | Generates and rewrites `ui-config.php`. Errors here break first-run setup and can leave installations in an unrecoverable state. |
| `src/Management.php` | **Admin Security** | Handles CSRF verification and all admin POST actions. Bugs here can create security vulnerabilities or corrupt configuration. |
| `src/Database.php` | **Data Layer** | DB facade with DDL embedded directly in code. Schema changes (adding columns, renaming tables) have no migration system — they must be handled manually. |
| `src/Databases/Mysql.php` | **Data Layer** | MySQL driver. Same concern as `Database.php` — DDL is in-code, no migration system. |
| `src/Databases/Sqlite.php` | **Data Layer** | SQLite driver. Same concern as above. |
| `src/Updater.php` | **Self-Update** | Fetches a remote zip and replaces files on disk. Bugs can corrupt installations or introduce a supply-chain risk. |
| `src/Certificates.php` | **SSL Security** | Manages the CA cert bundle used for all HTTPS calls. Incorrect handling can break all outbound requests or disable SSL verification. |
| `ui-config.php` | **Generated File** | Written by `OobeManager`. **Never edit manually** — it will be overwritten anyway and manual edits can break the boot sequence. |

## When to Use Plan Mode

Enter Plan Mode when:

1. **Modifying any file in the table above** — regardless of how small the change appears
2. **Changing any public method signature** in `Processor`, `CurlRequest`, or `Translation` — even adding a parameter with a default value, because WP/Joomla plugins may rely on positional arguments
3. **Adding or removing columns** in the SQLite or MySQL tables — requires coordinated manual DDL across all active installations
4. **Adding a new `on*` hook** to `Configuration.php` — must be documented and communicated to plugin maintainers

## What Plan Mode Looks Like

1. **Document** the proposed change: what file, what method/property, what the new signature/behavior is
2. **Explain the impact**: which callers are affected (WP plugin, Joomla plugin, tests, other `src/` classes)
3. **List migration steps** if a DB schema change is involved
4. **Get user approval** before writing any code
5. **Update tests** — all Plan Mode changes require updated or new tests before the PR is mergeable

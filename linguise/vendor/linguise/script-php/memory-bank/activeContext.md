# Script-PHP — Active Context

## Last Updated
2026-04-06

## What Was Done
- Created the `memory-bank/` directory with full documentation for the Script-PHP project
- Created `AGENTS.md` as the primary AI agent guide for this workspace
- Documentation was bootstrapped from codebase exploration (no functional code was changed)

## Current State
No active development tasks. The memory bank represents the state of the codebase as of 2026-04-06.

## Key Things to Know
- The WordPress and Joomla plugins call `Processor::run()` directly — it is a shared public API
- `ui-config.php` is generated — never edit manually
- There is no database migration system — schema DDL is embedded in `src/Databases/*.php`
- Any change to public method signatures in `Processor`, `CurlRequest`, or `Translation` requires Plan Mode
- OOBE has two modes: with-token (migration of existing customers) and without-token (new installs)
- The PrestaShop adapter is the most complex — it uses `JsonWalker` for translating JSON AJAX responses

## Next Steps
_(none pending)_

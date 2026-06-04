---
name: scaffold-admin-action
description: Generates all boilerplate for a new admin POST action in the management interface. Creates the template partial, adds a stub handler to Management.php, adds a switch case in linguise.php, writes a test stub in ManagementTest.php, and updates memory-bank/managementFlow.md.
---

# Scaffold Admin Action

Generates all boilerplate for a new admin POST action in the Script-PHP management interface across 5 locations: template partial, Management.php handler, linguise.php switch case, ManagementTest.php test stub, and managementFlow.md documentation.

## Procedure

### Step 1 — Read reference files (read-only, do not modify)

Read these files to understand the current conventions:
1. `linguise.php` — the dispatch switch block (lines 94-125 for GET+POST actions, lines 126-141 for POST-only actions)
2. `src/Management.php` — handler method patterns with CSRF verification
3. `src/Session.php` — CSRF token generation and verification API


### Step 2 — Get action details

Ask the user for:
- **Action name**: kebab-case identifier, e.g., `clear-logs` (used in `linguise_action` param and as the CSRF action key)
- **Action description**: What the admin action does (for documentation)
- **HTTP method**: Whether the action is triggered via `$_GET['linguise_action']` or `$_POST['linguise_action']` in the management dispatch
- **Handler location**: Whether the handler goes in `Management.php` (standard) or `OobeManager.php` (OOBE/session actions like `login`, `logout`)

Derive:
- **Template file**: `src/templates/tpl/<action-name>.php`
- **CSRF action key**: `<action-name>` (used with `Session::verifyCsrfToken()`)
- **Test method name**: `test<ActionNameInPascalCase>()` (e.g., `testClearLogs`)

4. `src/templates/tpl/main-settings.php` — template partial access guard pattern
5. `tests/ManagementTest.php` — test method patterns for admin actions


### Step 3 — Create the template partial

Create `src/templates/tpl/<action-name>.php`:

```php
<?php

use Linguise\Script\Core\Database;
use Linguise\Script\Core\Templates\Helper as AdminHelper;

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');
defined('LINGUISE_AUTHORIZED') or die('Access denied.');

$options = Database::getInstance()->retrieveOtherParam('linguise_options');
$has_api_key = !empty($options['token']);

// TODO: Add template content for {ACTION_NAME} action
?>
```

The key requirements are:
- `defined('LINGUISE_MANAGEMENT') or die('No access to this page.');` guard
- `defined('LINGUISE_AUTHORIZED') or die('Access denied.');` guard (for admin-only content)
- Common imports for `Database` and `AdminHelper`
- Standard `$options` and `$has_api_key` initialization




### Step 4 — Add handler stub to Management.php

**⚠ PLAN MODE WARNING**: `src/Management.php` is a Plan Mode file. Before modifying, present the proposed change and get user approval.

Add a stub handler method to `src/Management.php`:

```php
/**
 * Handle the {ACTION_NAME} admin action.
 *
 * @return void
 */
public function {actionNamePascalCase}()
{
    // Start session and verify authorization
    $sess = Session::getInstance()->start();

    // Verify session
    if (!$sess->hasSession()) {
        $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Not authorized</div>';
        $oobe = OobeManager::getInstance();
        $oobe->run($message, []);
        return;
    }

    // Verify CSRF token
    if (!isset($_POST['_token'])) {
        HttpResponse::errorJSON('Missing CSRF token', 400);
        $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Missing CSRF token</div>';
        $oobe = OobeManager::getInstance();
        $oobe->run($message, []);
        return;
    }
    if (!$sess->verifyCsrfToken('{ACTION_NAME}', $_POST['_token'])) {
        $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Invalid CSRF token</div>';
        $oobe = OobeManager::getInstance();
        $oobe->run($message, []);
        return;
    }

    // TODO: Implement {ACTION_NAME} logic

    // Re-render the management panel with success message
    $notification_popup_msg = '<div class="linguise-notification-popup success"><span class="material-icons success">check</span>{ACTION_NAME} completed successfully</div>';
    $api_web_errors = [];
    $oobe = OobeManager::getInstance();
    $oobe->run($notification_popup_msg, $api_web_errors);
}
```

**CSRF pattern reference** (from existing handlers):
- Session authentication: `$sess->hasSession()` check before all else
- CSRF token: `verifyCsrfToken('<action-key>', $_POST['_token'])` where action-key is the kebab-case action name
- Error responses: Use `HttpResponse::errorJSON()` for API-style errors, or render the management panel with an error message for UI-style handlers
- Success: Re-render management panel via `OobeManager::getInstance()->run($message, $errors)`

**If handler goes in OobeManager.php instead**: Use the same CSRF pattern but render via `$this->run()` instead of `OobeManager::getInstance()->run()`.



### Step 5 — Add switch case to linguise.php

Add a `case` entry to the appropriate switch block in `linguise.php`:

**For GET-based actions** (under the `$_GET['linguise_action']` switch, around line 94-125):
```php
case '{ACTION_NAME}':
    $management->{actionNamePascalCase}();
    break;
```

**For POST-based actions** (under the `$_POST['linguise_action']` switch, around line 126-141):
```php
case '{ACTION_NAME}':
    $management->{actionNamePascalCase}();
    break;
```

If the handler is in `OobeManager`, use `$oobe_manager->{actionNamePascalCase}()` instead of `$management->...`.

Place the new case alphabetically or logically among the existing cases.


### Step 6 — Add test method stub to ManagementTest.php

Add a test method to `tests/ManagementTest.php`. Use the existing test structure for reference:

```php
public function test{ActionNamePascalCase}(): void
{
    $this->setRequest();
    $db = $this->provisionDatabaseStub();
    $this->setDefaultOptions($db);

    // Provide a logged in session with CSRF token
    $session = $this->setNewSession('csrf', 1234567890);
    $session->setSession('dummy', true);
    $token = $session->getCsrfToken('{ACTION_NAME}');

    $_POST['_token'] = $token;
    // TODO: Add any action-specific POST data

    $captured = $this->withCaptureResponse(function () {
        Management::getInstance()->{actionNamePascalCase}();
    });

    $this->assertStringContainsString('{ACTION_NAME} completed successfully', $captured);
    // TODO: Add more assertions for the action's specific behavior
}
```

**Test naming convention**: `test{ActionNamePascalCase}()` — e.g., `testClearLogs()`.

**Unhappy path tests**: Also add tests for:
```php
public function test{ActionNamePascalCase}Unauthorized(): void
{
    $this->setRequest();
    $db = $this->provisionDatabaseStub();
    $this->setDefaultOptions($db);

    // No session set
    $captured = $this->withCaptureResponse(function () {
        Management::getInstance()->{actionNamePascalCase}();
    });

    $this->assertStringContainsString('Not authorized', $captured);
}

public function test{ActionNamePascalCase}InvalidCsrf(): void
{
    $this->setRequest();
    $db = $this->provisionDatabaseStub();
    $this->setDefaultOptions($db);

    $session = $this->setNewSession('csrf', 1234567890);
    $session->setSession('dummy', true);

    // Set wrong token
    $_POST['_token'] = 'invalid-token';

    $captured = $this->withCaptureResponse(function () {
        Management::getInstance()->{actionNamePascalCase}();
    });

    $this->assertStringContainsString('Invalid CSRF token', $captured);
}
```




### Step 7 — Update memory-bank/managementFlow.md

Append a new row to the "Admin POST Actions" table in `memory-bank/managementFlow.md`:

```markdown
| `{ACTION_NAME}` | `Management::{actionNamePascalCase}()` | {ACTION_DESCRIPTION} |
```

If the handler is in `OobeManager`, use `OobeManager::{actionNamePascalCase}()` as the handler reference.

### Step 8 — Validation checklist

After generating all files, verify:
1. `php -l src/Management.php` — no syntax errors (especially after adding the new method)
2. `php -l linguise.php` — no syntax errors
3. `php -l src/templates/tpl/<action-name>.php` — no syntax errors
4. `php -l tests/ManagementTest.php` — no syntax errors
5. Run `./vendor/bin/phpunit tests/ManagementTest.php --filter test{ActionNamePascalCase}` to verify the test stub passes

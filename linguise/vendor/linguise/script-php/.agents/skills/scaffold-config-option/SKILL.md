---
name: scaffold-config-option
description: Scaffolds a new config option with optional admin UI.
---

# Scaffold Config Option

Scaffolds a new configuration option across up to seven files spanning the three
configuration layers (documented in `memory-bank/environment.md`) and the admin UI,
following the `compress_response` commit (`f9a09ef`) and the existing UI-exposed
options (`cache_enabled`, `cache_max_size`, `search_translations`).

## Procedure

### Step 1 — Read reference files (read-only, do not modify)

Read these files to understand the current conventions:

1. `Configuration.php` (root) — `public static` properties with docblocks (see `$compress_response`, `$force_ipv4` for the documented pattern)
2. `src/Configuration.php` — private properties with single-line `/** ... */` docblocks (see `$compress_response`, `$force_ipv4`, `$cache_ignore_parameters`)
3. `tests/ConfigurationTest.php` — `testGetReturnsPropertyValue()` default assertions (see `compress_response` assertion)
4. `memory-bank/environment.md` — "Key Properties" table (`| Property | Type | Default | Description |`)
5. `memory-bank/planModeFiles.md` — Plan Mode file list (all files touched except the test, template, and `environment.md` are listed)

If the option is UI-exposed (`--ui`), also read:

6. `src/OobeManager.php` — `createOptionsWithToken()` defaults (lines ~97-110) and `mergeConfig()` reload lines (lines ~740-755)
7. `src/Management.php` — `updateConfig()` sanitisation block (lines ~204-209) and the `$linguise_options` save array (lines ~211-217)
8. `src/templates/tpl/advanced.php` — `$translation_strings` entries plus the checkbox / number / text control patterns (`cache_enabled`, `cache_max_size`, `cache_ignore_parameters`, `cache_params_always_included`)

### Step 2 — Get option details

Ask the user for (or parse from the invocation, e.g. `/scaffold-config-option cache_ignore_query bool false --ui`):

- **Option name**: snake_case identifier, e.g. `cache_ignore_query` (used as the property name, DB key, and form field name in all seven files)
- **Type**: one of `bool`, `int`, `string`, `float` (drives the sanitisation snippet, test assertion, and form control)
- **Default**: PHP literal, e.g. `false`, `200`, `''`, `null` (must match in root `Configuration.php`, `src/Configuration.php`, and `environment.md`)
- **Description**: one-line human description (used for both docblocks and the `environment.md` row)
- **UI-exposed**: whether the option appears in the admin dashboard (`--ui` flag). Without it, touch only the four core files. With it, also touch the three UI files.

Derive:

- **PHP default literal**: `false` / `true` for `bool`, e.g. `200` for `int`, `''` for `string`, `null` for nullable
- **Test assertion**: `assertTrue` / `assertFalse` for `bool`, `assertSame(<default>, ...)` for `int`/`string`/`float`/`null`
- **Sanitiser** (UI only, see Step 7 table)
- **Form control** (UI only, see Step 8: checkbox for `bool`, number input for `int`/`float`, text input for `string`)

### Step 3 — Plan Mode warning

**⚠ PLAN MODE WARNING**: `Configuration.php` (root), `src/Configuration.php`, `src/OobeManager.php`, and `src/Management.php` are Plan Mode files (see `memory-bank/planModeFiles.md`). Before modifying any of them, present the proposed change and get user approval — consistent with the `scaffold-admin-action` skill warning.

Show the full list of files that will be written (4 core, or 7 with `--ui`) and wait for approval before writing.

### Step 4 — Add the public static property to root `Configuration.php`

Add the documented property alongside related options (cache options near `$cache_enabled` / `$cache_max_size`, otherwise under `/** Advanced configuration **`, before `$compress_response` / `$force_ipv4`):

```php
    /**
     * {DESCRIPTION}
     */
    public static ${OPTION_NAME} = {DEFAULT_LITERAL};
```

Reference pattern (from `f9a09ef`):

```php
    /**
     * Re-compress translated responses with the same Content-Encoding as the origin
     * response (gzip, deflate, br, ...) before sending them to the browser.
     * Set to false to send translated responses uncompressed.
     */
    public static $compress_response = false;
```

### Step 5 — Add the private property with docblock to `src/Configuration.php`

Add the single-line-docblock property in the matching section (`/** Basic configuration **/` for cache/search options, `/** Advanced configuration **/` otherwise):

```php
    /** {DESCRIPTION} */
    private ${OPTION_NAME} = {DEFAULT_LITERAL};
```

Reference pattern:

```php
    /** Re-compress the response with the origin Content-Encoding before sending it */
    private $compress_response = false;
```

No other change is needed here — `get()`, `set()`, `loadFile()`, and `toArray()` pick the property up via `property_exists()` / `get_object_vars()` reflection.

### Step 6 — Add an assertion to `tests/ConfigurationTest.php`

Add one line to `testGetReturnsPropertyValue()` next to the existing default assertions:

```php
        // Test default values
        $this->assertTrue($config->get('cache_enabled'));
        $this->assertTrue($config->get('compress_response'));
        // TODO: add the new assertion on the next line
```

Assertion by type:

| Type | Snippet |
|------|---------|
| `bool` true default | `$this->assertTrue($config->get('{OPTION}'));` |
| `bool` false default | `$this->assertFalse($config->get('{OPTION}'));` |
| `int` / `string` / `float` / `null` | `$this->assertSame({DEFAULT_LITERAL}, $config->get('{OPTION}'));` |

Reference pattern (from `f9a09ef`):

```php
        $this->assertTrue($config->get('compress_response'));
```

### Step 7 — Add a row to the property table in `memory-bank/environment.md`

Append a row to the "Key Properties" table under Layer 1, keeping related options together:

```markdown
| `${OPTION_NAME}` | `{TYPE}` | `{DEFAULT_FOR_DOCS}` | {DESCRIPTION} |
```

Reference pattern (from `f9a09ef`):

```markdown
| `$compress_response` | `bool` | `true` | Re-compress translated responses with the origin's `Content-Encoding` (gzip/deflate/br/zstd) before sending |
```

`DEFAULT_FOR_DOCS` is the value wrapped in backticks as rendered (`` `false` ``, `` `200` ``, `` `''` ``, `` `null` ``).

### Step 8 — UI only: add `db_options` default and reload lines in `src/OobeManager.php`

Two edits, both required:

**8a. `createOptionsWithToken()`** — add the default next to the other `Configuration::getInstance()->get(...)` lines so fresh OOBE installs persist it:

```php
        $options = [
            'token' => $token,
            'cache_enabled' => Configuration::getInstance()->get('cache_enabled'),
            'cache_max_size' => Configuration::getInstance()->get('cache_max_size'),
            'search_translations' => Configuration::getInstance()->get('search_translations'),
            '{OPTION_NAME}' => Configuration::getInstance()->get('{OPTION_NAME}'),
            'debug' => Configuration::getInstance()->get('debug'),
```

**8b. `mergeConfig()`** — reload the stored value into the runtime singleton on every admin boot, next to the existing `set(...)` lines:

```php
            Configuration::getInstance()->set('cache_enabled', $existing_options['cache_enabled']);
            Configuration::getInstance()->set('cache_max_size', $existing_options['cache_max_size']);
            Configuration::getInstance()->set('{OPTION_NAME}', $existing_options['{OPTION_NAME}']);
```

If the option is new and old DB rows may lack the key, use the backward-compatible fallback pattern (as with `cache_ignore_parameters`):

```php
            $new_option = isset($existing_options['{OPTION_NAME}']) ? $existing_options['{OPTION_NAME}'] : {DEFAULT_LITERAL};
            Configuration::getInstance()->set('{OPTION_NAME}', $new_option);
```

### Step 9 — UI only: add sanitisation in `src/Management.php` `updateConfig()`

Two edits inside `updateConfig()`:

**9a. Sanitise the POST value** next to the existing `$cache_enabled` / `$cache_max_size` lines:

| Type | Snippet |
|------|---------|
| `bool` (checkbox `value="1"`) | `$opt = isset($linguise_options['{OPTION}']) && $linguise_options['{OPTION}'] === '1' ? 1 : 0;` |
| `int` | `$opt = isset($linguise_options['{OPTION}']) ? (int)$linguise_options['{OPTION}'] : {DEFAULT_INT};` |
| `float` | `$opt = isset($linguise_options['{OPTION}']) ? (float)$linguise_options['{OPTION}'] : {DEFAULT_FLOAT};` |
| `string` | `$opt = isset($linguise_options['{OPTION}']) ? trim($linguise_options['{OPTION}']) : '{DEFAULT_STRING}';` |

Reference pattern:

```php
            $cache_enabled = isset($linguise_options['cache_enabled']) && $linguise_options['cache_enabled'] === '1' ? 1 : 0;
            $cache_max_size = isset($linguise_options['cache_max_size']) ? (int)$linguise_options['cache_max_size'] : 200;
            $cache_params_always_included = isset($linguise_options['cache_params_always_included']) ? trim($linguise_options['cache_params_always_included']) : '';
```

**9b. Persist it** in the `$linguise_options` save array next to the matching entries:

```php
            $linguise_options = [
                'token' => $token,
                'cache_enabled' => $cache_enabled,
                'cache_max_size' => $cache_max_size,
                '{OPTION_NAME}' => ${OPTION_VAR},
```

### Step 10 — UI only: add a form control in `src/templates/tpl/advanced.php`

Two edits:

**10a. Add a `$translation_strings` entry** in the relevant section (`cache` section for cache options, `translation_extra` / `advanced` otherwise):

```php
        '{option_key}' => [
            'title' => __('{Label}', 'linguise'),
            'help' => __('{Help text}', 'linguise'),
        ],
```

**10b. Add the control** next to the related option's markup:

Bool (checkbox, cf. `cache_enabled`):

```php
                <div class="flex flex-col mt-4">
                    <label class="linguise-slider-checkbox">
                        <input type="checkbox" class="slider-input" name="linguise_options[{OPTION_NAME}]" value="1" <?php echo isset($options['{OPTION_NAME}']) ? (AdminHelper::checked($options['{OPTION_NAME}'], 1)) : (''); ?> />
                        <span class="slider"></span>
                        <span class="slider-label font-semibold">
                            <?php echo esc_html($translation_strings['cache']['{option_key}']['title']); ?>
                            <span class="material-icons help-tooltip" data-tippy="<?php echo esc_attr($translation_strings['cache']['{option_key}']['help']); ?>">
                                help_outline
                            </span>
                        </span>
                    </label>
                </div>
```

Int (number, cf. `cache_max_size`):

```php
                <div class="flex flex-col mt-2">
                    <label for="opt-{OPTION_SLUG}" class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['cache']['{option_key}']['title']); ?>
                    </label>
                    <input id="opt-{OPTION_SLUG}" type="number" class="linguise-input rounder mt-1" name="linguise_options[{OPTION_NAME}]" value="<?php echo esc_attr((int)$options['{OPTION_NAME}']); ?>" min="0" max="1000" step="1" style="width: 7rem;" data-linguise-int="{OPTION_NAME}" />
                </div>
```

String (text, cf. `cache_params_always_included`):

```php
                <div data-id="{OPTION_SLUG}-wrapper" class="flex flex-col mt-2">
                    <label for="opt-{OPTION_SLUG}" class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['cache']['{option_key}']['title']); ?>
                        <span class="material-icons help-tooltip" data-tippy="<?php echo esc_attr($translation_strings['cache']['{option_key}']['help']); ?>">
                            help_outline
                        </span>
                    </label>
                    <input id="opt-{OPTION_SLUG}" type="text" class="linguise-input rounder mt-1" name="linguise_options[{OPTION_NAME}]" value="<?php echo esc_attr($options['{OPTION_NAME}'] ?? ''); ?>" placeholder="<?php echo esc_attr($translation_strings['cache']['{option_key}']['placeholder']); ?>" style="width: 20rem;" data-linguise-text="{OPTION_NAME}" />
                </div>
```

### Step 11 — Validation checklist

After generating all files, verify:

1. `php -l Configuration.php` — no syntax errors
2. `php -l src/Configuration.php` — no syntax errors
3. `php -l tests/ConfigurationTest.php` — no syntax errors
4. `php -l src/OobeManager.php` — no syntax errors (UI only)
5. `php -l src/Management.php` — no syntax errors (UI only)
6. `php -l src/templates/tpl/advanced.php` — no syntax errors (UI only)
7. Run `./vendor/bin/phpunit tests/ConfigurationTest.php` to verify the new assertion passes

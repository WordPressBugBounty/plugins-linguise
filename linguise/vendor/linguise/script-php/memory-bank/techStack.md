# Script-PHP — Tech Stack

## PHP

| Property | Value |
|----------|-------|
| Minimum version | PHP `^7.0.7` |
| Required extensions | `ext-curl`, `ext-json`, `ext-zip` |
| Autoloading | PSR-4 (via Composer) |
| ORM | None — raw `mysqli` + `SQLite3` |
| Coding style | Not formally enforced; follows general PHP community conventions |

## PHP Composer Dependencies

### Runtime

Script-PHP has **zero runtime Composer dependencies** beyond built-in PHP extensions (`ext-curl`, `ext-json`, `ext-zip` are declared but are part of PHP core). All functionality is implemented in the `src/` classes.

### Development

| Package | Version | Purpose |
|---------|---------|---------|
| `phpunit/phpunit` | `^9` | Test framework |
| `phpunit/php-code-coverage` | `9.2.32` | Coverage reporting |
| `vlucas/phpdotenv` | `^5.6` | `.env` file loading in test bootstrap |

## PHP Autoloading (PSR-4)

| Namespace | Directory |
|-----------|-----------|
| `Linguise\Script\Core\` | `src/` |
| `Linguise\Script\Core\Templates\` | `src/templates/` |

The user-facing `Configuration.php` uses the `Linguise\Script` namespace (no `Core` suffix).

## Frontend (Admin UI)

| Property | Value |
|----------|-------|
| Node version | 18.17.0 (pinned via Volta) |
| Package manager | npm (Yarn 1.22.18 also pinned via Volta) |
| Bundler | Webpack 5 |
| Transpiler | Babel 7 (`@babel/core`, `@babel/preset-env`) |
| CSS | SCSS → compiled with `sass-loader`, extracted with `mini-css-extract-plugin` |
| Minification | `terser-webpack-plugin` (JS), `css-minimizer-webpack-plugin` (CSS) |

### Runtime Frontend Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `codemirror` | `^5.61` | Code editor in the admin panel |
| `rangeslider-js` | `^3.2.5` | Range slider UI component |
| `tippy.js` | `^6.3.1` | Tooltips |
| `script-js` | private | Linguise-internal shared JS library (private Bitbucket repo) |

### External (CDN, not bundled)

- `jQuery` — declared as a Webpack external; loaded via CDN in the admin templates

### Webpack Entry Points

| Entry | Output JS | Output CSS |
|-------|-----------|------------|
| `assets/js/admin.js` | `assets/js/admin.bundle.js` | `assets/css/admin.bundle.css` |
| `assets/js/login.js` | `assets/js/login.bundle.js` | `assets/css/login.bundle.css` |

Images (flags) are emitted to `assets/images/flags-rounded/` and `assets/images/flags-rectangular/`.

Source maps are only generated in non-production mode.

## External Services

| Service | URL | Purpose |
|---------|-----|---------|
| Translation server | `translate.linguise.com` | Receives HTML; returns translated HTML |
| API server | `api.linguise.com` | Domain configuration, subscription, token validation |
| Linguise CDN | (version JSON URL) | Version manifest + zip download for self-update |
| CA cert source | Mozilla/curl CDN | `cacert.pem` download via `Certificates.php` |

## Deployment Pipelines (Bitbucket CI/CD)

Docker images: `linguise/deployment:v3` (tests), `linguise/deployment:latest` (packaging / production).

### Pipeline Overview

| Pipeline | Trigger | Purpose |
|----------|---------|---------|
| **PR tests** (`**`) | Every pull request | Install deps, build frontend, run PHPUnit (no coverage) |
| **run-test-coverage** | Manual (custom) | Same as PR tests but with `XDEBUG_MODE=coverage`; stores coverage to `tests/reports/**` |
| **generate-package** | Manual (custom) | Build production zip and upload to Bitbucket Downloads |
| **Publish to production** | Push to `master` | Full release flow — see below |

### PR Test Steps

1. Copy `.env.development` → `.env`, remove all `.env.*` files
2. Install `php7.4-curl`, `php7.4-dom`, `php7.4-zip`
3. `yarn install && yarn build`
4. `composer install --prefer-dist --no-interaction --no-progress`
5. `php -d memory_limit=-1 vendor/bin/phpunit --no-coverage`

### Production Publish Flow (`master` branch)

1. Read version from `.version` file; **abort** if git tag `vX.Y.Z` already exists
2. Copy `.env.production` → `.env`, remove all `.env.*` files
3. Assert version constant is set correctly in `linguise.php`
4. `composer install` (no-dev, optimized autoloader)
5. Download fresh `cacert.pem` from `https://curl.se/ca/cacert.pem`
6. `yarn install && NODE_ENV=production yarn build`
7. Build `script-php.zip` (see exclusions below)
8. Compute MD5 of zip; write `php-script-update.json` with version, md5, URL, date
9. Create git tag `vX.Y.Z` via Bitbucket API
10. Upload versioned zip (`X.Y.Z.zip`) to Bitbucket Downloads
11. `rsync` zip + update JSON to `deploy@$IP_SERVER_PROD_WEB:/var/www/linguise.com/public_html/files/`
12. SSH: run `change-files-permissions.sh` (via `sudo root`)
13. Purge Cloudflare cache for `script-php.zip` and `php-script-update.json`

### Files Excluded from Production Zip

`node_modules/`, `*.scss`, `tests/`, `bitbucket-pipelines.yml`, `composer.*`, `phpunit.xml`, `webpack.config.js`, `yarn.lock`, `package*.json`, `.env*`, `Readme.md`, `.git*`

### Required Pipeline Variables

| Variable | Used in |
|----------|---------|
| `API_USERNAME` / `API_PASSWORD` | Bitbucket API calls (tag + upload) |
| `IP_SERVER_PROD_WEB` | rsync / SSH target |
| `SSH_DEFAULT_PORT` | rsync / SSH port |
| `CLOUDFLARE_CACHE_KEY` | Cloudflare cache purge bearer token |

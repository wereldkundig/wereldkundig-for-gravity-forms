# Gravity Forms Wereldkundig Add-On

Subscribes Gravity Forms submitters to Wereldkundig themes. A visitor ticks a **Wereldkundig subscribe me** checkbox on a form; on submit the add-on sends their email (and name, and any other mapped fields) to the Wereldkundig platform, which adds them to the themes (and optionally only some of their categories) chosen for that checkbox.

- Requires WordPress 6.9+, PHP 8.2+, Gravity Forms 2.5+ (developed on 2.10).
- Works on single sites and multisite (network-activated); every site has its own settings, cache, feeds and cron event.
- Text domain `gravityformswereldkundig`; all user-facing strings are English and translatable.

**How to set up and use the add-on, and the hooks reference, are in the docs site: [`docs/src/content/docs/`](docs/src/content/docs/).** This README is for working on the add-on: how it's built, the internals the public docs leave out, and how to test it. Keep internals here; everything under `docs/` is published.

## Contents

- [How it works in short](#how-it-works-in-short)
- [Code structure](#code-structure)
- [Internals](#internals)
- [Debugging](#debugging)
- [Local development](#local-development)
- [Tests](#tests)
- [Documentation](#documentation)
- [Releasing](#releasing)

## How it works in short

```
Form editor                 Submit                                  Platform
───────────                 ──────                                  ────────
Subscribe field ──┐         entry saved
 (themes and      │           │
  categories)     │           ├─ form has a Wereldkundig feed?
Themes dropdown ◄─┤           │    yes → queued, sent in the background ─┐
 from the cache   │           │    no  → sent right away ────────────────┤
                  │           ▼                                          ▼
Settings save ────┴──► GET /v1/subscribe/fields          POST /v1/subscribe
Hourly WP-Cron ──────► (themes + subscriber fields,       (email, names, mapped
                        cached in an option)               fields, checked themes)
```

## Code structure

```
gravityformswereldkundig.php    bootstrap: autoloader, Component, deactivation hook
app/
  Component.php                 on gform_loaded: registers the add-on and the field
  Addon.php                     GFFeedAddOn: settings, feeds, status notice, no-feed path
  ApiAuthSettingField.php       masked Basic Auth settings field
  FieldsRefresh.php             hourly WP-Cron refresh of the cache
  SubscribeField.php            the wk_subscribe field and its editor settings
  Submission.php                what happens on submit
  Mapper.php                    form entry → subscriber fields
  Api/Client.php                API calls, cache, credentials, mock
tests/                          Pest: Unit (mocked) and Live (real platform); Playwright: E2E
docs/                           the docs site (Starlight), not shipped with the plugin
```

- The plugin has its own small PSR-4 autoloader for `app/`, so it works without `vendor/`. Composer's autoloader is loaded when present, for third-party packages added later.
- Everything loads on `gform_loaded`, so nothing happens without Gravity Forms.
- The add-on slug is `wereldkundig`; it stays that way so existing feeds and settings keep working.

## Internals

The user-facing behaviour is described in the docs; these are the parts behind it.

### Submit

`app/Submission.php` handles both the feed and the no-feed path (`gform_after_submission`, `Addon::maybe_subscribe_without_feed()`), in the order of [the docs](docs/src/content/docs/reference/entry-notes-and-errors.md#what-happens-on-submit).

- Return values for Gravity Forms: `true` success, a `WP_Error` failure, `null` skipped. Gravity Forms saves them as the feed status of the entry, the error with its code and message.
- Theme IDs are public, but checking them against the cached themes stops tampered or stale IDs. Themes added by the `wk_gf_subscribe_themes` filters are not checked.
- The theme filters only run when at least one checked field has a valid theme.

### Sending in the background

Feeds are processed asynchronously, like Brevo (`$_async_feed_processing = true` in `Addon`). Gravity Forms core does the work; no Action Scheduler is involved:

1. On submit `GFFeedAddOn` queues the feed instead of running it. The queue (`GF_Feed_Processor`, a fork of WP Background Processing) is stored as a site option.
2. On `shutdown` Gravity Forms sends a non-blocking loopback request to its own `admin-ajax.php`, which runs `process_feed()` and adds the entry note.
3. A WP-Cron healthcheck (every 5 minutes) picks up whatever the loopback didn't.

`Addon::is_feed_error_retryable()` decides which failures `gform_max_async_feed_attempts` may retry.

### The Wereldkundig API client

`app/Api/Client.php`, API docs at https://docs.wereldkundig.nl/api. The request format is in [the docs](docs/src/content/docs/reference/subscribe-request.md).

- `GET /v1/subscribe/fields` returns the subscriber `fields` (key => type, label, required) and `options.categories`: the platform's **themes**. Theme IDs are normalized to `theme-{id}`. Names come with HTML entities from WordPress and are decoded; output is escaped again on render.
- Chosen categories are sent **without** their theme. The platform adds the theme of every category itself, but a theme it receives also gets all of that theme's main categories, which would subscribe to the whole theme after all.
- Success is `"success": true` in the response; its `data` has the platform `user_id` and the `rejected_options`.
- The platform also accepts `upsert` (default `false`) and `confirmed`, which the add-on doesn't send. See the notes in [todo.md](todo.md).

### The mock

`Client` has a mock of both endpoints, shaped like the OpenAPI spec. It **only** runs under the test suite: `tests/bootstrap.php` defines `WK_GF_RUNNING_TESTS` and PHPUnit must be loaded. On a site it never runs, not even as a fallback for missing credentials or an unreachable API, because it always succeeds and would silently drop real subscribers. Inside the tests the `wk_api_use_mock` filter turns it off to test the real HTTP path. That filter is left out of the public hooks reference (`extra.wp-hooks.ignore-hooks` in `docs/tools/composer.json`).

### Themes and subscriber fields cache

- The `GET /v1/subscribe/fields` response is cached in the `wk_gf_api_fields` option (not autoloaded) with the time it was fetched, keyed by the platform URL and Basic Auth code, so changing either invalidates it.
- Within one request the result is kept in memory, a failed fetch included, so the API is called at most once per request. A failed fetch never overwrites the cache.
- Gravity Forms re-renders the whole form editor after every form save, which runs the subscribe field's editor hooks (`SubscribeField::editor_settings()` and `SubscribeField::editor_js()`) on every form, with or without a subscribe field. So the editor reads the cache only (`Client::themes_setup(false, false)`) and never calls the API.
- The refresh (`app/FieldsRefresh.php`, event `wk_gf_refresh_fields`) is scheduled while the API is configured: when saving the settings, and on any admin page load via `Addon::init_admin()` → `FieldsRefresh::sync()`, which also covers sites set up before the refresh existed, or only with constants. Without credentials, after uninstalling or deactivating, it is removed.
- Brevo keeps its lists in a 5 minute transient instead; whichever request needs them after that waits for the fetch.
- See [todo.md](todo.md) for remembering a failed fetch across requests.

### Settings page

- **Forms > Settings > Wereldkundig** (`Addon::settings_url()`). The connection status is rendered from stored data only, after a save is processed, so it is current right after saving.
- A failed fetch after saving or in the background is stored in the `wk_gf_api_fields_error` option (message, error code, time and `source`: `settings` or `cron`). Any successful fetch deletes it; removing the credentials or uninstalling does too.
- The Basic Auth code is never put in the page (`app/ApiAuthSettingField.php`, field type `wk_api_auth`, like Mailchimp for WP does with its API key). The mask is the first few characters and 16 asterisks: at most 4 characters and at most a third of the code. Nothing from the end (the end of a base64 Basic Auth code is the end of the password) and not its length. Saving the unchanged mask keeps the saved code (base64 never contains `*`). On a Gravity Forms version without the settings field classes it falls back to a password field.
- Gravity Forms 2.7.17+ encrypts add-on settings when `GF_ENCRYPTION_KEY` is defined; `Client::credentials()` decrypts them. Without that key the code is stored as plain text in `gravityformsaddon_wereldkundig_settings`, like other add-ons (Brevo, Laposta) do.

### Saved field settings

`wk_theme_ids` and `wk_category_ids` are cleaned to valid IDs when the form is saved (`SubscribeField::sanitize_settings()`). The editor saves the selection in the browser (`wkSetFieldThemes()`), which is why the browser tests exist.

## Debugging

- **Gravity Forms logging** and **entry notes**: see [Troubleshooting](docs/src/content/docs/troubleshooting.md).
- **Ray** (spatie-ray plugin): when `ray()` exists, every API call (request, shortened auth header, status, raw body, result) and every subscribe from a submission (form, entry, feed, checked fields, themes, subscriber, result) is sent to Ray, green on success and red on failure.

## Local development

- **Herd SSL**: Herd signs `*.test` with its own CA, which PHP doesn't trust, so calls to `https://wereldkundig-platform.test` fail with cURL error 60. The mu-plugin `wp-content/mu-plugins/wk-local-ssl.php` turns off SSL verification for `localhost` and `*.test` only, and only when `WP_ENVIRONMENT_TYPE` is `local`.
- WP-CLI is handy for checking things on a site, e.g. `wp --url=wereldkundig-platform.test/lansingerland cron event run wk_gf_refresh_fields`.

## Tests

Pest 1 with Brain Monkey and Mockery (`yoast/wp-test-utils`); WordPress and Gravity Forms are stubbed in `tests/bootstrap.php`.

```bash
composer install
npm install           # also sets up the git hooks (husky)
composer test         # composer validate, PHPCS, unit tests (against the mock) and PHPStan
composer test:unit    # only the unit tests
composer test:phpcs   # coding standards of app/
composer fix:phpcs    # fix what PHPCS can fix automatically
composer test:live    # real calls to a test platform
composer test:phpstan # static analysis (level 8) of app/
```

Before every `git push`, husky (`.husky/pre-push`) runs `composer test` and `composer docs:hooks`, and stops the push when either fails or when `docs:hooks` changed the hooks reference in `docs/src/data/hooks/` (commit it, then push again). Skip it once with `git push --no-verify`.

PHPCS (`phpcs.xml.dist`) checks the WordPress coding standards (WordPress-Extra, PHP 8.2+ compatibility) with three changes: PSR-4 file names (`Api/Client.php`, not `class-client.php`), camelCase methods and classes allowed next to snake_case, and Yoda conditions not required.

WordPress and Gravity Forms come from stubs (`php-stubs/wordpress-stubs`, `php-stubs/gravity-forms-stubs`), so it runs without a WordPress install or Gravity Forms. `tests/phpstan/gravityforms.stub` corrects Gravity Forms docblocks that are narrower than the code, such as `rgar()` and `add_note()`.

The live tests read `.env.local.tests` (copy `.env.local.tests.example`, never commit it):

- `WK_TEST_API_URL`, `WK_TEST_API_AUTH`: the test platform; the tests are skipped without them.
- `WK_TEST_API_CA_FILE`: Herd's CA, so the `*.test` certificate verifies.
- `WK_TEST_API_SUBSCRIBE_EMAIL`: optional; when set, a real subscriber with this address is created on the platform.

### Browser tests

Playwright drives the form editor of a local site: it checks a theme and a category of the subscribe field, clicks **Save Form** and checks what was stored (`tests/E2E/`). The unit tests can't cover this, because `wkSetFieldThemes()` saves the selection in the browser.

```bash
npm ci
npx playwright install chromium
npm run test:e2e              # headless
npm run test:e2e -- --headed  # watch the browser
npm run test:e2e -- --ui      # step through
```

They read `.env.local.tests` too:

- `WK_E2E_URL`: the local site, e.g. `https://wereldkundig-platform.test/lansingerland/`. It must have `WP_ENVIRONMENT_TYPE` `local` and a cached theme with categories (save the Wereldkundig settings first).
- `WK_E2E_WP_PATH`: the WordPress root for WP-CLI, relative to this plugin (default `../../..`).

Each run creates a local super admin `wk-e2e` with a new password (logged in at the network's `wp-login.php`, since a subsite's own login redirects to itself locally), creates a throwaway form `WK E2E …` and deletes that form afterwards.

## Documentation

The public docs are a [Starlight](https://starlight.astro.build) site in `docs/`, with its own `package.json`. The pages are Markdown in `docs/src/content/docs/`; the sidebar is in `docs/astro.config.mjs`.

The site is English at `/` and Dutch at `/nl/`. Every page has a Dutch twin in `docs/src/content/docs/nl/` with the same path, which the language picker switches between, so add or change a page in both languages. A missing Dutch page falls back to English with a notice. In the Dutch pages, links need the `/nl/` prefix and anchors follow the Dutch headings. Gravity Forms and WordPress labels use their Dutch names, while the add-on's own labels stay English, because the add-on has no Dutch translation yet. Sidebar labels have a `translations` entry in `astro.config.mjs`. The strings of the component overrides are in `docs/src/content/i18n/{en,nl}.json`. `starlight-links-validator` fails the build on a broken link or anchor in either language. The Dutch hook descriptions are in `docs/src/data/hooks-translations/nl.json`, each with the English `source` it was translated from. After a docblock change and `composer docs:hooks`, update that hook's `source` and its Dutch text, or the docs build fails and names the hook. The look follows [docs.wereldkundig.nl](https://docs.wereldkundig.nl), dark only: colours and type in `docs/src/styles/wereldkundig.css`, the logo, header link, lead line and footer in the component overrides in `docs/src/components/`. It meets WCAG 2.2 AA (checked with axe-core at phone to desktop widths): keep text at 4.5:1 contrast or more against its background, and form field borders at 3:1; the reasons for each fix are in the comments of `wereldkundig.css` and `Search.astro`.

The Claude Code skill `.claude/skills/update-docs` holds these rules as instructions: the page map, the Dutch terminology and screen-label rules, and two helpers. `hook_translations.py` reports and records the Dutch hook descriptions, and `gf_label.py` looks up Dutch Gravity Forms labels. Claude loads it when asked to update the documentation.

### First setup

To do once, before the site goes live:

1. Commit the generated and lock files as well: `docs/src/data/hooks/*.json`, `docs/tools/composer.lock` and `docs/package-lock.json`. The CI check fails until the hooks JSON is committed.
2. Add the static site in [Sevalla](https://sevalla.com), from this repository and the `main` branch: root directory `docs`, build command `npm run build`, publish directory `dist`, Node version Latest LTS (Sevalla doesn't read `.nvmrc`), error file `404.html`. Turn on pull request previews to get a preview URL per branch.
3. Set `site` in `docs/astro.config.mjs` once the domain is known. Starlight uses it for canonical URLs; the sitemap is skipped until then.

### Running locally

```bash
cd docs
npm ci
npm run dev      # http://localhost:4321
npm run build    # into docs/dist/
```

### Hooks reference

The filters on the Hooks page are generated from the docblocks in `app/`:

```bash
composer docs:hooks   # needs PHP 8.3+
```

- It runs [wp-hooks/generator](https://github.com/wp-hooks/generator) from `docs/tools/`, which has its own `composer.json`: the generator needs PHP 8.3+ and pins its own `nikic/php-parser`, which would clash with Pest's.
- It writes `docs/src/data/hooks/filters.json` and `actions.json`. **Commit them**: the docs build doesn't run PHP.
- It fails when a hook has no docblock directly above its `apply_filters()` or `do_action()` call; the generator would leave that hook out. Write the docblock's first sentence as plain text (it's shown as is) and the rest as Markdown. Hooks that shouldn't be public go under `extra.wp-hooks.ignore-hooks` in `docs/tools/composer.json`.
- Dynamic hooks use double-quoted names, `"wk_gf_subscribe_themes_{$form_id}"`, so the docs show the name instead of a concatenation.

### CI and deploy

- `.github/workflows/docs.yml` runs on changes to `app/` or `docs/`: it fails when the committed hooks JSON doesn't match the code, and builds the site.
- Sevalla builds and deploys every push to `main`, set up as in [First setup](#first-setup).
- `docs/` and `.github/` are `export-ignore` in `.gitattributes`. Leave them out of any other deploy of the plugin as well.

## Releasing

The add-on is released on GitHub and on WordPress.org as `gravityformswereldkundig`, by two workflows that use [10up's deploy actions](https://github.com/10up/action-wordpress-plugin-deploy).

1. Set the new version in three places: `Version:` and `const VERSION` in `gravityformswereldkundig.php`, and `Stable tag:` in `readme.txt`. Add a `= x.y.z =` section to the changelog in `readme.txt`.
2. Commit and push to `main`, then tag and push the tag: `git tag v1.2.0 && git push origin v1.2.0`.

`.github/workflows/release.yml` then:

- runs `composer test`, and stops when the tag doesn't match those three version numbers;
- builds the package with `git archive` from the tagged commit, so the `export-ignore` lines in `.gitattributes` decide what ships. A new file or folder ships unless it is export-ignored. The workflow checks that every file in `app/` is in the package and that the development files aren't;
- creates a GitHub Release with the zip, using the changelog section of that version as notes;
- deploys the same package, `readme.txt` and `.wordpress-org/` to WordPress.org SVN (trunk, `tags/x.y.z` and `assets/`).

A version with `alpha`, `beta`, `rc`, `dev` or `test` in it (`v1.2.0-beta1`) is a pre-release: a GitHub Release only, and `Stable tag:` stays at the last stable version. Run the workflow by hand (**Actions > Release > Run workflow**) to only test and build a branch; the package is attached to the run as an artifact.

`.github/workflows/plugin-assets.yml` deploys only `readme.txt` and `.wordpress-org/` (icon, banner, screenshots), for changes between releases. It runs on a push to `main` that changes nothing else, or by hand. What goes in `.wordpress-org/` is in its [README](.wordpress-org/README.md).

Both need the repository secrets `SVN_USERNAME` and `SVN_PASSWORD`: a WordPress.org account with commit access to the plugin, and its SVN password (**Account & Security** on the WordPress.org profile, not the login password).

The SVN repository only exists once WordPress.org has approved the plugin. Submit the first version by hand: download the zip from a manual run of the Release workflow and upload it at https://wordpress.org/plugins/developers/add/. If the review assigns a different slug, change `SLUG` in both workflows (and the text domain to match) before the first tag.

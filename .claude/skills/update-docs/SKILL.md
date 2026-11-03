---
name: update-docs
description: Writes and updates the public documentation of the Gravity Forms Wereldkundig add-on, the bilingual Starlight site in docs/ (English at /, Dutch at /nl/), including the Dutch page twins, sidebar and component translations, the generated hooks reference and its hand-translated Dutch hook descriptions. Use this whenever the user asks to update, write, fix, extend or translate "the documentation", "the docs", the docs site, a docs page, the hooks reference or the Dutch/Nederlandse docs, and also after a code change in app/ that changes behaviour, settings, labels, messages, error codes or a hook docblock, since those must be reflected in both languages, even if the user only says "update the docs for this".
---

# Updating the add-on documentation

The docs are a [Starlight](https://starlight.astro.build) site in `docs/` (its own `package.json`). They are the public manual for site builders and developers who use the add-on, so they describe what a user sees and can do, not how the code is built (that goes in `README.md`).

The site is bilingual, and both languages are always updated together: a change in only one language leaves the other wrong, and the language picker sends people straight from one to the other.

Paths and commands below are relative to the plugin root (`wp-content/plugins/gravityformswereldkundig`).

## The map

| What | Where |
|---|---|
| English pages | `docs/src/content/docs/**.md(x)` |
| Dutch pages | `docs/src/content/docs/nl/**`: **same file names and paths** as English |
| Sidebar (labels + Dutch `translations`) | `docs/astro.config.mjs` |
| Strings of our component overrides | `docs/src/content/i18n/en.json`, `nl.json`; keys declared in `docs/src/content.config.ts` |
| Component overrides (logo label, footer, header link, lead line, hooks list) | `docs/src/components/` |
| Generated hooks reference (never edit by hand) | `docs/src/data/hooks/filters.json`, `actions.json` |
| Dutch hook descriptions (hand-written) | `docs/src/data/hooks-translations/nl.json` |
| Look and contrast (WCAG 2.2 AA, dark only) | `docs/src/styles/wereldkundig.css` |
| Developer notes about the docs | `README.md`, section "Documentation" |

Which page covers what:

| Topic | Page (same path under `nl/`) |
|---|---|
| Requirements, (de)activation, uninstall | `getting-started/installation.md` |
| Platform URL, Basic Auth code, connection status, `WK_API_*` constants | `getting-started/connect.md` |
| First form walkthrough | `getting-started/first-form.md` |
| The subscribe field, themes/categories, checked by default, visibility | `guides/subscribe-field.md` |
| Feeds, field mapping, auto-detected fields | `guides/feeds-and-mapping.md` |
| Async feeds, loopback, retries | `guides/background-sending.md` |
| Fields cache, hourly WP-Cron refresh | `guides/themes-and-refresh.md` |
| Filters, actions, examples | `reference/hooks.mdx` (list comes from `HookList.astro`) |
| Submit flow, entry notes, every error code | `reference/entry-notes-and-errors.md` |
| API endpoints and payload | `reference/subscribe-request.md` |
| Capabilities | `reference/capabilities.md` |
| Options, field settings, identifiers | `reference/stored-data.md` |
| Consent, https, what is sent | `reference/security-and-privacy.md` |
| Checklist and common problems | `troubleshooting.md` |
| Landing page | `index.mdx` |

## Workflow

### 1. Find out what changed

When the docs follow a code change, read the change first (`git diff`, or the files in `app/`) and list every user-visible effect: settings and their labels, defaults, messages and error codes, entry notes, behaviour, hook names, parameters and docblocks. Then search both languages for every place that mentions it: a rename or a new default usually touches several pages, the README and the hooks.

```bash
grep -rn "auto-detect\|automatisch" docs/src/content/docs README.md
```

Quote labels, messages and codes exactly as the code produces them; check the string in `app/` rather than paraphrasing.

### 2. Write the English page

Match the existing pages: short sentences, second person ("you"), sentence-case headings, UI labels in **bold** exactly as on screen, menu paths as **Forms > Settings > Wereldkundig**, code, option names and hook names in backticks, and Starlight asides (`:::note`, `:::tip`, `:::caution`) for the occasional warning. Internal links are root-relative with a trailing slash: `[field mapping](/guides/feeds-and-mapping/#auto-detected-fields)`. Anchors are the slug of the heading. The page's `description` frontmatter is shown as a lead line under the title, so keep it one plain sentence.

PHP examples never use arrow functions (`fn () => 20`) or one-line ternaries: write a multi-line `function () { … }` with a `return` on its own line, and an `if` instead of `? :`, like the examples on `reference/hooks.mdx`.

### 3. Write the Dutch twin

Read `references/dutch.md` before writing Dutch: it has the terminology, the tone and the rules for screen labels. The essentials:

- Same path under `nl/`, translated `title` and `description`.
- Links get the `/nl/` prefix (Starlight doesn't rewrite Markdown links), and anchors follow the **Dutch** headings: `/nl/guides/feeds-and-mapping/#automatisch-gevonden-velden`. Hook anchors (`#wk_api_timeout`) are the same in both languages.
- Gravity Forms and WordPress screen labels in Dutch, looked up with `scripts/gf_label.py`; the add-on's own labels, messages, entry notes and error messages stay English, because that is what a Dutch admin sees.
- Code blocks, hook names, option names, error codes and payload keys are not translated.
- An `.mdx` page that imports a component needs one more `../` under `nl/`.

### 4. New page, sidebar, component strings

- New page: create both files, then add it to the sidebar in `docs/astro.config.mjs` with `translations: { nl: '…' }`, like the other items.
- New or changed text in a component override: never hard-code it. Add a key to both `src/content/i18n/en.json` and `nl.json`, declare it in the `extend` schema in `src/content.config.ts`, and render it with `Astro.locals.t('wk.…')`.

### 5. Hooks

The filters on the hooks page come from the docblocks in `app/`, not from Markdown. To change a hook description, edit the docblock (first sentence plain text, the rest Markdown) and regenerate:

```bash
composer docs:hooks        # needs PHP 8.3+; commit the regenerated docs/src/data/hooks/*.json
```

The Dutch descriptions are written by hand in `docs/src/data/hooks-translations/nl.json`. Each entry stores the English it was translated from as `source`, and the docs build fails on the Dutch hooks page when an entry is missing, outdated or names a removed hook. After every regeneration:

```bash
python3 .claude/skills/update-docs/scripts/hook_translations.py              # what needs translating
# translate: edit description / long_description / params of those entries in nl.json
python3 .claude/skills/update-docs/scripts/hook_translations.py --accept NAME  # then record the new English as source
```

Only `--accept` a hook after its Dutch text says the same as the new English; accepting first would hide the outdated translation from the build check. For a hook without an entry, `--accept` creates one with the English text as a placeholder, which you translate straight away. Delete entries the report lists as removed. `long_description` supports inline `code` in backticks only. Examples on `reference/hooks.mdx` (both languages) are hand-written and need updating separately.

### 6. Verify

```bash
cd docs && npm run build
```

The build must end with "All internal links are valid." and "Complete!". It fails on a broken link or anchor in either language (`starlight-links-validator`) and on a missing or outdated hook translation. Then check the result:

- Every English page has a Dutch twin: `find docs/src/content/docs -name '*.md*' | sed 's#.*/docs/##; s#^nl/##' | sort | uniq -c | awk '$1!=2'` prints nothing.
- Open the changed pages in `docs/dist/` (or `npm run dev`) in both languages; the hook tables on `/nl/reference/hooks/` show Dutch.
- When the change came from code, `composer test` still passes, and the CI hooks check would pass: `docs/src/data/hooks/*.json` are regenerated and committed.

Mention anything you couldn't resolve, such as documentation that contradicts the code, instead of guessing which one is right.

## What not to touch

- `docs/src/data/hooks/*.json`: generated, only via `composer docs:hooks`.
- `docs/dist/` and `docs/.astro/`: build output.
- The theme and contrast choices in `wereldkundig.css` and `ThemeProvider`/`ThemeSelect`: the site is dark only, like docs.wereldkundig.nl, and every colour was chosen for WCAG contrast (see the comments there).
- The validator's `exclude` for `/reference/hooks/#…` in `astro.config.mjs`: those anchors are rendered by `HookList.astro`, which the validator can't see.

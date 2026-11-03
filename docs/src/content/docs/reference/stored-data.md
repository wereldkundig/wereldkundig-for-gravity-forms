---
title: Stored data
description: The options, field settings and identifiers the add-on stores in WordPress.
---

## Options

Every site has its own.

| Option | Content |
|---|---|
| `gravityformsaddon_wereldkundig_settings` | The Gravity Forms settings: `api_url` and `api_auth`. |
| `wk_gf_api_fields` | The cached themes and subscriber fields: `key`, `fetched_at` and `response`. |
| `wk_gf_api_fields_error` | The last failed fetch: `message`, `code`, `time` and `source` (`settings` or `cron`). Deleted by the next successful fetch. |

## The subscribe field

The field type is `wk_subscribe`. Its settings are saved in the form:

- `wk_theme_ids`: the IDs of the checked themes;
- `wk_category_ids`: the category keys checked in those themes.

Both are cleaned up to valid IDs when the form is saved.

## Identifiers

| | |
|---|---|
| Add-on slug | `wereldkundig`, e.g. as `addon_slug` of a feed |
| WP-Cron event | `wk_gf_refresh_fields` |
| Text domain | `gravityformswereldkundig` |

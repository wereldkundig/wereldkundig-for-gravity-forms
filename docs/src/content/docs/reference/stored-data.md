---
title: Stored data
description: The options, feed settings and identifiers the add-on stores in WordPress.
---

## Options

Every site has its own.

| Option | Content |
|---|---|
| `gravityformsaddon_wereldkundig_settings` | The Gravity Forms settings: `api_url` and `api_auth`. |
| `wk_gf_api_fields` | The cached themes and subscriber fields: `key`, `fetched_at` and `response`. |
| `wk_gf_api_fields_error` | The last failed fetch: `message`, `code`, `time` and `source` (`settings` or `cron`). Deleted by the next successful fetch. |

## The subscribe field

The field type is `wk_subscribe`. Its only own setting, `wk_default_checked`, is saved in the form.

## Feeds

A Wereldkundig feed saves these in its `meta`, next to Gravity Forms' own `feedName`, `mapped_fields_*` and `feed_condition_*`:

- `subscribe_field`: the field ID of the subscribe checkbox, the only one of the form when it has one;
- `wk_themes`: theme ID => the category keys checked in that theme, an empty list meaning the whole theme. For example `{"theme-1": [], "theme-2": ["category-36-37"]}`.

Only valid theme and category IDs are saved.

## Identifiers

| | |
|---|---|
| Add-on slug | `wereldkundig`, e.g. as `addon_slug` of a feed |
| WP-Cron event | `wk_gf_refresh_fields` |
| Text domain | `wereldkundig-for-gravity-forms` |

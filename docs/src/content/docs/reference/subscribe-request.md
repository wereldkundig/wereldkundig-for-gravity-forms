---
title: The subscribe request
description: The platform endpoints the add-on uses and what it sends, for developers using the filters.
---

The add-on uses two endpoints of the Wereldkundig API. The full API is documented at [docs.wereldkundig.nl/api](https://docs.wereldkundig.nl/api).

- Base URL: `{platform}/wp-json/wereldkundig`, changeable with [`wk_gf_api_base_url`](/reference/hooks/#wk_gf_api_base_url).
- Every request sends the Basic Auth header and JSON, and waits at most 10 seconds ([`wk_gf_api_timeout`](/reference/hooks/#wk_gf_api_timeout)).

## GET /v1/subscribe/fields

Returns the subscriber fields and the platform's **themes**, each with categories and child categories. This answer is what the add-on [caches](/guides/themes-and-refresh/).

Theme IDs are always written as `theme-{id}`: `1`, `theme-1` and `theme_1` all become `theme-1`.

## POST /v1/subscribe

Subscribes one person to all chosen themes, in one request:

```json
{
  "email": "jan@example.nl",
  "first_name": "Jan",
  "last_name": "Jansen",
  "options": { "theme-1": true, "category-36-37": true, "category-36-38": true }
}
```

- `options` holds a whole theme as `theme-{id}`, or only the chosen categories of a theme as the platform's category keys: `category-{id}`, `category-{parent}-{id}` for a child category, or `select-all-{id}` for everything in a parent category.
- Chosen categories are sent without their theme; the platform adds the theme itself. Sending the theme as well would subscribe to the whole theme.
- `email` is always sent. Other fields are only sent when they have a value, so an empty field can't clear a value stored on the platform.
- Field keys must match `[a-z0-9_]+`. Values that aren't text or numbers are left out.
- The platform's answer contains the platform `user_id` and any `rejected_options`. Both appear in the [entry note](/reference/entry-notes-and-errors/#entry-notes).

The fields come from [`wk_gf_subscriber_pre_subscribe`](/reference/hooks/#wk_gf_subscriber_pre_subscribe) and the themes from [`wk_gf_subscribe_themes`](/reference/hooks/#wk_gf_subscribe_themes): theme ID => category keys, an empty array meaning the whole theme.

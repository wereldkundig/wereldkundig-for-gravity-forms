---
title: Themes and the hourly refresh
description: Where the themes in a feed come from, when they are fetched, and how to refresh them.
---

The themes, their categories and the subscriber fields come from the platform in one request. Your site keeps them in a cache, so the form editor never waits for the platform.

## When the platform is contacted

| Where | Uses the cache | Contacts the platform when nothing is cached |
|---|---|---|
| Saving **Forms > Settings > Wereldkundig** | no, always fetches | yes |
| The background refresh, every hour | no, always fetches | yes |
| The form editor | yes | **no** |
| The settings page: connection status | yes | **no** |
| A feed's **Themes** and **Map Fields** | yes | yes |
| The feed list: the **Themes** column | yes | **no** |
| A submission: checking the themes | yes | yes |

The form editor never contacts the platform. Gravity Forms rebuilds the editor after every form save, so a slow or unreachable platform would otherwise slow down saving every form.

The cache belongs to the platform URL and Basic Auth code: change either one, and the themes are fetched again. A failed fetch never replaces the cache, so the themes you had keep working.

## The background refresh

The cache doesn't expire by itself. It is refreshed:

- in the background by WP-Cron, every hour, so new themes show up within the hour;
- right away when you **save the Wereldkundig settings**.

The refresh is scheduled while the platform is connected, and removed when the credentials are removed, or when the add-on is deactivated or uninstalled. The first run is one hour after it is scheduled.

Change the interval with a WP-Cron schedule name. Unknown names fall back to hourly:

```php
add_filter('wk_gf_refresh_fields_recurrence', function () {
    return 'twicedaily';
});
```

Change the delay before the first run, in seconds, with [`wk_gf_refresh_fields_initial_delay`](/reference/hooks/#wk_gf_refresh_fields_initial_delay).

## WP-Cron

WP-Cron only runs when the site gets visits. On a site with little traffic, or with `DISABLE_WP_CRON`, let the server call `wp-cron.php`, or run `wp cron event run --due-now`.

Run the refresh by hand with WP-CLI:

```bash
wp cron event run wk_gf_refresh_fields
# On multisite, per site:
wp cron event run wk_gf_refresh_fields --url=example.nl/site
```

## A theme was removed on the platform

A feed that still has a removed theme checked fails with **Invalid Wereldkundig theme** on submit. Its other themes are still subscribed. The feed list shows the removed theme by its ID, such as `theme-9`. Open the feed, check its themes and save the feed.

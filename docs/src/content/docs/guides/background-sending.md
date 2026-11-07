---
title: Sending in the background
description: How feeds are sent after the submit, what it needs from your server, and how to retry or switch it off.
---

Forms with a Wereldkundig feed send the subscriber in the background, so the visitor never waits for the platform. Gravity Forms does this itself:

1. On submit, Gravity Forms queues the feed instead of running it.
2. The visitor sees the confirmation right away.
3. At the end of the request, Gravity Forms sends a request to its own site (a loopback request), which sends the subscriber and adds the entry note.
4. A WP-Cron check, every 5 minutes, picks up anything the loopback request missed.

A form **without** a Wereldkundig feed sends nothing; see [Feeds and field mapping](/guides/feeds-and-mapping/).

## Loopback requests

Sending in the background needs loopback requests to work; check **Forms > System Status**. They fail when the site is behind HTTP Basic Auth, behind some firewalls, or with a broken SSL setup. Queued feeds then wait for WP-Cron, which only runs when the site gets visits.

## Retrying failed feeds

A failed feed isn't retried by default. Raise Gravity Forms' `gform_max_async_feed_attempts` filter to retry:

```php
add_filter('gform_max_async_feed_attempts', function () {
    return 3;
});
```

Errors that a retry can't fix are never retried: an invalid theme or email address, a subscribe checkbox that is no longer on the form, missing credentials or an `http://` URL, subscriber data that can't be converted to JSON, and any `4xx` response except `408` and `429`. Timeouts, `5xx` responses and themes that couldn't be loaded are retried. See [Entry notes and errors](/reference/entry-notes-and-errors/).

## Send during the submit instead

To send during the submit, for all forms or for one form or feed, use Gravity Forms' `gform_is_feed_asynchronous` filter:

```php
add_filter('gform_is_feed_asynchronous', function ($async, $feed) {
    if ('wereldkundig' === rgar($feed, 'addon_slug')) {
        return false;
    }

    return $async;
}, 10, 2);
```

The visitor then waits for the platform's answer, up to the [request timeout](/reference/hooks/#wk_api_timeout).

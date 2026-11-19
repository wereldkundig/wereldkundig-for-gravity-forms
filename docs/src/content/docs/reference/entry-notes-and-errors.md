---
title: Entry notes and errors
description: What happens on submit, the notes an entry gets, and every error code with its cause.
---

## What happens on submit

A form without a Wereldkundig feed sends nothing. For every feed of the form whose condition matches:

1. Spam entries are skipped.
2. The form has no subscribe field, or several and the feed's **Subscribe checkbox** isn't one of them: the error `wk_gf_subscribe_field_missing`. A form with one subscribe field always uses that one.
3. That checkbox isn't checked: skipped. Nothing is noted, only logged when [logging](/troubleshooting/#logging) is on.
4. The feed's themes are checked against the platform's themes, and only categories of that theme are kept.
5. The subscriber fields are [mapped](/guides/feeds-and-mapping/), then the [`wk_gf_subscribe_themes` and `wk_gf_subscriber_pre_subscribe`](/reference/hooks/) filters run. They can change or skip the subscribe.
6. One request to the platform, with all themes and categories of the feed.
7. The entry gets a note with the result.

## Entry notes

On success:

> **reader@example.com was sent to Wereldkundig (platform user ID 12). Themes: Nieuwsbrief; Wijken (Noord, Zuid).**

When the platform updated an existing subscriber (upsert / legacy `/v1/`), the note says so instead:

> **reader@example.com was already a Wereldkundig subscriber and was updated (platform user ID 12). Themes: Nieuwsbrief; Wijken (Noord, Zuid).**

The note also lists any options the platform rejected.

On failure:

> **reader@example.com was not sent to Wereldkundig: {message} (code: {code})**

Errors found before the request is sent read **{message} (code: {code})**.

A failure is a feed error: it shows as an entry note, in the Gravity Forms log, and as the feed status of the entry.

## Errors before the request

| Code | Message | Cause |
|---|---|---|
| `wk_gf_subscribe_field_missing` | The subscribe checkbox chosen in the feed is not on the form; choose one in the feed settings. Subscriber not sent. | The form has no subscribe field: add one. Or it has several, and the feed's **Subscribe checkbox** was removed from the form or never chosen: choose one in the feed. |
| `wk_gf_themes_unavailable` | Could not load the Wereldkundig themes; check the API connection. Subscriber not sent. | No themes at all: the platform isn't connected or can't be reached. |
| `wk_gf_theme_invalid` | Invalid Wereldkundig theme: theme-9. | The feed has a theme the platform doesn't have (anymore), or no theme at all (**none selected**). The valid themes are still subscribed, but the result counts as failed. |
| `wk_gf_email_invalid` | No valid email address to subscribe. | The feed doesn't map the email address (and [auto-detected fields](/guides/feeds-and-mapping/#auto-detected-fields) are off), or the email field is empty or not a valid address. |

## Errors from the platform

| Code | Cause |
|---|---|
| `wk_gf_api_credentials_missing` | No platform URL or Basic Auth code. |
| `wk_gf_api_insecure_url` | The URL doesn't use `https://`. |
| `wk_gf_api_json_encode` | The subscriber data couldn't be converted to JSON, e.g. text that isn't valid UTF-8. Nothing was sent. |
| `member-exists` | The email address is already a member of the platform site, and the feed's **Existing subscribers** option is off. The platform saves nothing, not even the new themes. Check that option to update the member instead. |
| The platform's own error code, or `wk_gf_api_http` | Any other response that isn't a success. The note shows the platform's message when it sends one. |
| `wk_gf_api_subscribe_failed` | The platform answered, but didn't confirm the subscribe. |
| `wk_gf_api_json` | The platform's answer isn't valid JSON. |
| `wk_gf_api_fields_invalid` | The themes and subscriber fields couldn't be read from the platform's answer. |
| `http_request_failed` | WordPress couldn't complete the request, e.g. a timeout. |

Which of these are retried in the background is described under [Retrying failed feeds](/guides/background-sending/#retrying-failed-feeds).

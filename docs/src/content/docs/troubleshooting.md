---
title: Troubleshooting
description: What to check when nobody gets subscribed, and where to find out why.
---

## Nothing gets subscribed

1. Is the subscribe field **Visible**? Hidden and Administrative fields aren't posted, see [Visibility](/guides/subscribe-field/#visibility).
2. Does **Forms > Settings > Wereldkundig** show **Successfully connected**? If not, save the settings and read the error. See [Connect to the platform](/getting-started/connect/#connection-status).
3. Does the form have a feed under **Form Settings > Wereldkundig**? Without one, nothing is sent. With more than one subscribe field, does the feed's **Subscribe checkbox** name the box the visitor checked?
4. Are the feed's themes still checked? Themes removed from the platform fail with **Invalid Wereldkundig theme**.
5. Does the feed's condition match, and do loopback requests work under **Forms > System Status**? Otherwise queued feeds wait for WP-Cron. See [Sending in the background](/guides/background-sending/#loopback-requests).
6. Read the entry's notes and the Gravity Forms log.

## Entry notes

Every submit with a checked subscribe box gets a note saying whether the subscriber was sent, or why not. The codes are listed under [Entry notes and errors](/reference/entry-notes-and-errors/).

## Logging

Go to **Forms > Settings > Logging** and enable **Wereldkundig**. Skips, failures and successes are then logged, including submits that were skipped because no subscribe box was checked.

## Common problems

**A new theme doesn't show up in the feed.**
The feed uses the cached themes. Save the Wereldkundig settings to fetch them now, or wait for the [hourly refresh](/guides/themes-and-refresh/).

**The note says `wk_gf_email_invalid`.**
The subscriber has no email address. Map the Email field under **Map Fields** in the form's feed, or turn on [auto-detected fields](/guides/feeds-and-mapping/#auto-detected-fields).

**The note says `member-exists`.**
The email address is already a member of the platform site, and the platform saved nothing. Add the themes for this person on the platform itself.

**Requests time out.**
The platform gets 10 seconds to answer. Raise it with [`wk_gf_api_timeout`](/reference/hooks/#wk_gf_api_timeout). Feeds send in the background, so visitors don't wait.

**The background refresh failed.**
The settings page shows when and why. The themes you had keep working; the next refresh, or saving the settings, tries again.

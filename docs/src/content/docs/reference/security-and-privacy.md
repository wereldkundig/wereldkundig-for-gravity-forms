---
title: Security and privacy
description: How the add-on handles consent, the Basic Auth code and the data it sends.
---

## Consent

- Nothing is sent unless the visitor checked the subscribe box.
- **Checked by default** is off, and turning it on shows a GDPR warning. See [Checked by default](/guides/subscribe-field/#checked-by-default).

## What is sent

The email address, and the names and other fields that are [mapped](/guides/feeds-and-mapping/), together with the chosen themes. Empty fields aren't sent.

Besides its settings and the cached themes, the add-on stores no data of its own. The submitted data stays in the Gravity Forms entry, and the entry notes include the email address. Gravity Forms logging, when you turn it on, also records the subscribes.

## The connection

- **https only.** The Basic Auth code travels with every request, so the platform URL must use `https://`. This is checked when saving the settings and again before every request. Plain `http://` is only allowed for `localhost`, `*.localhost` and `*.test`.
- **The code stays hidden.** The settings page only shows the start of the Basic Auth code, and debug output shortens it to 4 characters. Keep it out of the database entirely with [constants in wp-config.php](/getting-started/connect/#credentials-in-wp-configphp).
- **Encryption.** Gravity Forms 2.7.17 and later encrypt add-on settings when `GF_ENCRYPTION_KEY` is defined.

## Input and output

- The themes of a submitted field are checked against the platform's themes before subscribing, so changed or outdated theme IDs aren't sent.
- Submitted values are sanitized, and names and labels from the platform are escaped on output.

---
title: Feeds and field mapping
description: Why a form needs a Wereldkundig feed, how fields are mapped, and how to find fields automatically instead.
---

A form needs a feed to send subscribers: the feed's field mapping tells the add-on which form field holds the email address. A feed also adds conditional logic and sending in the background. Without a feed, the subscribe checkbox only works when you turn on [auto-detected fields](#auto-detected-fields).

| | Without a feed | With a feed |
|---|---|---|
| When it is sent | during the submit | in the background, right after the submit |
| Field mapping | none: only with auto-detected fields turned on | per subscriber field |
| Conditional logic | the field's own | the field's own **and** the feed's condition |
| Errors | entry note | feed error: an entry note and the Gravity Forms log |

## Add a feed

Open the form and go to **Form Settings > Wereldkundig**, then add a feed. You can add feeds once the [platform is connected](/getting-started/connect/). A feed has:

- **Name**: for your own overview.
- **Map Fields**: which form field fills each subscriber field.
- **Conditional Logic**: when enabled, the subscriber is only sent when the condition matches. The subscribe checkbox must still be checked.

Feeds can be duplicated.

## Map fields

**Map Fields** lists every subscriber field the platform offers, such as email, first name, middle name and last name. None of them are required, but map at least the email address: without it, the subscriber isn't sent and the entry gets the error `wk_email_invalid`.

- The email field only offers Email, Hidden and Text form fields.
- Disabled parts of a Name field, such as the prefix or suffix, aren't offered.
- A mapping you choose always wins over the automatic detection below, when that is turned on.
- A field that is empty on submit isn't sent, so it can't clear a value that is already stored on the platform.

When the subscriber fields can't be loaded from the platform, the feed page shows an error instead of the mapping. Saving the feed then keeps the existing mapping.

## Auto-detected fields

Fields aren't detected automatically by default. Turn it on with the [`wk_gf_auto_map_fields`](/reference/hooks/#wk_gf_auto_map_fields) filter:

```php
add_filter('wk_gf_auto_map_fields', '__return_true');
```

Then empty mappings, and forms without a feed, are filled in on submit:

- **Email**: the first Email field.
- **First, middle and last name**: the first Name field. A simple Name field only fills the first name.
- Without a Name field: the first Text or Hidden field labelled *first name* / *voornaam*, *middle name* / *tussenvoegsel(s)*, and *last name* / *surname* / *achternaam*. Otherwise, a field labelled exactly *name* or *naam* fills the first name.

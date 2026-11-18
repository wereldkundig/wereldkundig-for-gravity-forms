---
title: Feeds and field mapping
description: Why a form needs a Wereldkundig feed, how it chooses the checkbox and the themes, and how fields are mapped or found automatically.
---

A form needs a feed to send subscribers. The feed chooses which subscribe checkbox gives consent, which themes and categories to subscribe to, and which form field holds the email address. It also adds conditional logic, and sends in the background right after the submit. Without a feed, nothing is sent, even when the box is checked.

## Add a feed

Open the form and go to **Form Settings > Wereldkundig**, then add a feed. You can add feeds once the [platform is connected](/getting-started/connect/). A feed has:

- **Name**: for your own overview.
- **Subscribe checkbox**: the **Wereldkundig subscribe me** field of this form that gives consent. Only a choice when the form has more than one; otherwise the feed uses the only one.
- **Themes**: the themes and categories to subscribe to. Check at least one.
- **Map Fields**: which form field fills each subscriber field.
- **Conditional Logic**: when enabled, the subscriber is only sent when the condition matches. The subscribe checkbox chosen above must still be checked.

The feed list shows the name and the themes of each feed. Feeds can be duplicated.

## Subscribe checkbox

The feed only sends the subscriber when its subscribe checkbox is checked. Which checkbox that is depends on the number of **Wereldkundig subscribe me** fields on the form:

- **One**: there is nothing to choose. The feed shows the field by label (or admin label) and field ID, followed by "the only subscribe checkbox of this form", and always uses it. That includes a feed saved before the field was added, and a feed whose chosen field was replaced by another one.
- **Several**, for example one per newsletter: **Subscribe checkbox** is a required list of those fields. Give each checkbox its own feed, with its own themes. Every feed whose checkbox is checked sends its own request. When the chosen field is later removed from the form, the entry gets the error `wk_gf_subscribe_field_missing`; choose another checkbox in the feed.
- **None**: the feed shows a notice instead. You can still save the feed; once you add the field in the form editor, the feed uses it without being edited again. Until then, entries get the error `wk_gf_subscribe_field_missing`.

When the form had one subscribe field and you add a second, existing feeds keep the checkbox they were saved with.

## Themes and categories

**Themes** shows a checkbox for every theme on the platform, with that theme's categories underneath.

- A checked theme **without** checked categories is subscribed as a whole.
- Check categories to subscribe to **only** those categories of the theme.
- A parent category is shown as **Alles in …** (everything in it), with its child categories indented underneath.
- Categories of an unchecked theme are disabled and not saved.

Saving a feed without a checked theme shows **Check at least one theme.**

The themes come from the cache on your site, filled from the platform. A theme that was just added on the platform appears after the [next refresh](/guides/themes-and-refresh/), or right away when you save the Wereldkundig settings. When no themes can be loaded, the feed shows **Could not load the themes from Wereldkundig.** instead, and saving the feed keeps the themes it already had.

## Map fields

**Map Fields** lists every subscriber field the platform offers, such as email, first name, middle name and last name. None of them are required, but map at least the email address: without it, the subscriber isn't sent and the entry gets the error `wk_gf_email_invalid`.

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

Then empty mappings are filled in on submit:

- **Email**: the first Email field.
- **First, middle and last name**: the first Name field. A simple Name field only fills the first name.
- Without a Name field: the first Text or Hidden field labelled *first name* / *voornaam*, *middle name* / *tussenvoegsel(s)*, and *last name* / *surname* / *achternaam*. Otherwise, a field labelled exactly *name* or *naam* fills the first name.

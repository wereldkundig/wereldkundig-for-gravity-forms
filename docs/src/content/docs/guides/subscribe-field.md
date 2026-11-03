---
title: The subscribe field
description: The Wereldkundig subscribe me checkbox, its themes and categories, and the settings that matter for consent.
---

The **Wereldkundig subscribe me** field, in the **Standard Fields** group, adds one checkbox to a form. When a visitor checks it and submits the form, they are subscribed to the themes chosen for that field.

- The field label is the checkbox label; there is no separate label above it.
- It supports the usual field settings: description, CSS class, required, visibility, admin label, label placement and conditional logic.
- The markup matches Gravity Forms' own checkbox field, so your theme styles it the same way.
- Entries and exports show **Yes** when the box was checked, and nothing otherwise.

## Themes and categories

The **Themes** setting shows a checkbox for every theme on the platform, with that theme's categories underneath.

- A checked theme **without** checked categories is subscribed as a whole.
- Check categories to subscribe to **only** those categories of the theme.
- A parent category is shown as **Alles in …** (everything in it), with its child categories indented underneath.
- Categories of an unchecked theme are disabled and not saved.

One field can subscribe to several themes at once. A form can also have several subscribe fields, for example one checkbox per newsletter. All checked fields are sent together, in one request. When several fields choose the same theme, any field that chooses the whole theme wins; otherwise their categories add up.

The themes come from the cache on your site, not straight from the platform. A theme that was just added on the platform appears after the [next refresh](/guides/themes-and-refresh/), or right away when you save the Wereldkundig settings.

When no themes are cached, the setting shows a red notice instead:

- **Configure Wereldkundig API settings**: no credentials yet, see [Connect to the platform](/getting-started/connect/).
- **No Wereldkundig themes available**: credentials are set, but no themes were fetched. Save the Wereldkundig settings and check the connection status.

Users who may open the Wereldkundig settings get the notice as a link to the settings page.

## Checked by default

**Checked by default** shows the checkbox already checked when the form loads. It is off by default.

:::caution
A pre-checked box is not valid consent under the GDPR (EU Court of Justice, *Planet49*). Only turn this on when visitors expect to be subscribed by submitting the form. The form editor shows the same warning.
:::

- It only applies to a fresh form. After a validation error, or when going back a page, the visitor's own choice is shown, so an unchecked box stays unchecked.
- An entry only shows as checked when the visitor actually checked the box.

## Visibility

Keep the field **Visible**. A field set to **Hidden** or **Administrative** isn't posted by the visitor, so its value is empty and nobody is subscribed.

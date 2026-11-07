---
title: The subscribe field
description: The Wereldkundig subscribe me checkbox, how it connects to a feed, and the settings that matter for consent.
---

The **Wereldkundig subscribe me** field, in the **Standard Fields** group, adds one checkbox to a form. When a visitor checks it and submits the form, they are subscribed to the themes of the [feed](/guides/feeds-and-mapping/) that chose this checkbox.

- The field label is the checkbox label; there is no separate label above it.
- It supports the usual field settings: description, CSS class, required, visibility, admin label, label placement and conditional logic.
- The markup matches Gravity Forms' own checkbox field, so your theme styles it the same way.
- Entries and exports show **Yes** when the box was checked, and nothing otherwise.

## Themes

The field itself has no themes. You choose them in a [Wereldkundig feed](/guides/feeds-and-mapping/#themes-and-categories) of the form. When the form has more than one subscribe field, the feed also chooses the checkbox it belongs to, and the field settings show a notice with a link to the form's feeds. With one subscribe field, the feed uses that one.

A form can have several subscribe fields, for example one checkbox per newsletter. Add a feed for each checkbox, with its own themes. Without a feed, a checked box sends nothing.

## Checked by default

**Checked by default** shows the checkbox already checked when the form loads. It is off by default.

:::caution
A pre-checked box is not valid consent under the GDPR (EU Court of Justice, *Planet49*). Only turn this on when visitors expect to be subscribed by submitting the form. The form editor shows the same warning.
:::

- It only applies to a fresh form. After a validation error, or when going back a page, the visitor's own choice is shown, so an unchecked box stays unchecked.
- An entry only shows as checked when the visitor actually checked the box.

## Visibility

Keep the field **Visible**. A field set to **Hidden** or **Administrative** isn't posted by the visitor, so its value is empty and nobody is subscribed.

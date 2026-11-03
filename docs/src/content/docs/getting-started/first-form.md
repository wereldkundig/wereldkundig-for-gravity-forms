---
title: Your first form
description: Add the subscribe checkbox to a form, choose the themes and test a submission.
---

1. Open a form in the form editor. It needs an **Email** field.
2. Add a **Wereldkundig subscribe me** field from the **Standard Fields** group.
3. Under **Themes**, check one or more themes. A new field has the first theme checked. Check categories of a theme to subscribe to only those.
4. Change the field label. It is the text next to the checkbox, so replace "Wereldkundig subscribe me" with something like "Yes, send me the newsletter".
5. Save the form. Go to **Form Settings > Wereldkundig**, add a feed and under **Map Fields** choose the Email field for the email address. Save the feed.
6. Open the form and submit it with the box checked.
7. Open the entry. Its notes say whether the subscriber was sent, for example: **reader@example.com was sent to Wereldkundig (platform user ID 12). Themes: Nieuwsbrief.**

The feed is what sends the subscriber, in the background right after the submit. Without a feed, nothing is sent: the add-on only finds the Email and Name fields by itself when you turn on [auto-detected fields](/guides/feeds-and-mapping/#auto-detected-fields).

In the same [feed](/guides/feeds-and-mapping/) you can map more fields, such as the name, and add conditional logic.

:::tip
Does the **Themes** setting show **Configure Wereldkundig API settings** or **No Wereldkundig themes available**? Then [connect to the platform](/getting-started/connect/) first, or save the settings to fetch the themes again.
:::

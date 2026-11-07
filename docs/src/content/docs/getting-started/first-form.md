---
title: Your first form
description: Add the subscribe checkbox to a form, choose the themes in a feed and test a submission.
---

1. Open a form in the form editor. It needs an **Email** field.
2. Add a **Wereldkundig subscribe me** field from the **Standard Fields** group.
3. Change the field label. It is the text next to the checkbox, so replace "Wereldkundig subscribe me" with something like "Yes, send me the newsletter".
4. Save the form. Go to **Form Settings > Wereldkundig** and add a feed.
5. In the feed, check one or more themes under **Themes**. With more than one subscribe field on the form, also choose the field under **Subscribe checkbox**. Check categories of a theme to subscribe to only those.
6. Under **Map Fields**, choose the Email field for the email address. Save the feed.
7. Open the form and submit it with the box checked.
8. Open the entry. Its notes say whether the subscriber was sent, for example: **reader@example.com was sent to Wereldkundig (platform user ID 12). Themes: Nieuwsbrief.**

The feed is what sends the subscriber, in the background right after the submit. Without a feed, nothing is sent.

In the same [feed](/guides/feeds-and-mapping/) you can map more fields, such as the name, and add conditional logic.

:::tip
Does the feed show **Could not load the themes from Wereldkundig.** instead of the themes? Then [connect to the platform](/getting-started/connect/) first, or save the settings to fetch the themes again.
:::

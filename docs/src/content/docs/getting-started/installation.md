---
title: Installation
description: Requirements, installing and activating the add-on, and removing it again.
---

## Requirements

- WordPress 6.9 or later
- PHP 8.2 or later
- Gravity Forms 2.5 or later
- A Wereldkundig platform, and the Basic Auth code Wereldkundig supplies for it

The add-on works on single sites and on multisite networks. On a network every site has its own settings, feeds and themes.

## Install and activate

1. Put the `wereldkundig-for-gravity-forms` folder in `wp-content/plugins/`, or upload the plugin zip on the **Plugins** screen.
2. Activate the plugin. On multisite, network-activate it.
3. Continue with [Connect to the platform](/getting-started/connect/).

The add-on only loads when Gravity Forms is active.

## Deactivate and uninstall

- **Deactivating** stops the background refresh of the themes on the current site. After a network deactivation, the other sites keep their scheduled refresh, which then does nothing.
- **Uninstalling** from **Forms > Settings > Uninstall** removes the settings and the feeds, the cached themes, the stored error and the scheduled refresh. It needs the `gravityforms_wereldkundig_uninstall` [capability](/reference/capabilities/).

---
title: Installation
description: Requirements, installing and activating the add-on, and removing it again.
---

## Requirements

- WordPress 6.9 or later
- PHP 8.2 or later
- Gravity Forms 2.5 or later
- A Wereldkundig platform, and the Basic Auth code Wereldkundig supplies for it.

The add-on works on single sites and on multisite networks. On a network every site has its own settings, feeds and themes.

## Install and activate

:::note
The "Wereldkundig for Gravity Forms" plugins is currently being reviewed by the WordPress.org plugin team.
In the meanwhile, please download the release [wereldkundig-for-gravity-forms.1.1.0.zip](https://github.com/wereldkundig/wereldkundig-for-gravity-forms/releases/tag/v1.1.0) from GitHub.
:::

1. Download .zip [wereldkundig-for-gravity-forms.1.1.0.zip](https://github.com/wereldkundig/wereldkundig-for-gravity-forms/releases/tag/v1.1.0) from GitHub.
2. Extract and put the `wereldkundig-for-gravity-forms` folder in `wp-content/plugins/`, or upload the plugin zip on the **Plugins** screen.
3. Activate the plugin. On multisite, network-activate it.
4. Continue with [Connect to the platform](/getting-started/connect/).

The add-on only loads when Gravity Forms is active.

### Loading via Composer

Add repo to your `composer.json` and then run `composer require wereldkundig/wereldkundig-for-gravity-forms`.

```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/wereldkundig/wereldkundig-for-gravity-forms.git"
    }
],
```

## Deactivate and uninstall

- **Deactivating** stops the background refresh of the themes on the current site. After a network deactivation, the other sites keep their scheduled refresh, which then does nothing.
- **Uninstalling** from **Forms > Settings > Uninstall** removes the settings and the feeds, the cached themes, the stored error and the scheduled refresh. It needs the `gravityforms_wereldkundig_uninstall` [capability](/reference/capabilities/).

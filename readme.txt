=== Gravity Forms Wereldkundig Add-On ===
Contributors: wereldkundig, jmslbam
Tags: gravity forms, newsletter, subscribe, subscription, wereldkundig
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Subscribe Gravity Forms submitters to the themes of your Wereldkundig platform.

== Description ==

A visitor ticks a **Wereldkundig subscribe me** checkbox on a Gravity Forms form. On submit, the add-on sends their email address (and name, and any other fields you map) to your Wereldkundig platform, which subscribes them to the themes, or only some of their categories, chosen for that checkbox.

* A **Wereldkundig subscribe me** field for the form editor, with the themes and categories it subscribes to.
* A Wereldkundig feed per form to map the email address, names and other subscriber fields, with conditional logic.
* Subscribers are sent in the background right after the submit; the result is added to the entry as a note.
* The themes and subscriber fields are fetched from the platform when you save the settings and refreshed every hour.
* The Basic Auth code is never shown in full, and can be kept out of the database with constants in `wp-config.php`.
* Works on single sites and multisite networks, with settings per site.

Nothing is sent unless the visitor checked the box.

The add-on requires [Gravity Forms](https://www.gravityforms.com/) 2.5 or later and a Wereldkundig platform with the Basic Auth code Wereldkundig supplies for it.

== Installation ==

1. Install and activate Gravity Forms 2.5 or later.
2. Upload the plugin through **Plugins > Add New Plugin**, or put the `gravityformswereldkundig` folder in `wp-content/plugins/`.
3. Activate the plugin. On multisite, network-activate it.
4. Go to **Forms > Settings > Wereldkundig**, enter the platform URL and Basic Auth code, and save.
5. Add a **Wereldkundig subscribe me** field to a form, check its themes, and add a Wereldkundig feed under **Form Settings > Wereldkundig**.

== Frequently Asked Questions ==

= Where do I get the platform URL and Basic Auth code? =

From Wereldkundig, for your own platform.

= Can I keep the Basic Auth code out of the database? =

Yes. Define `WK_API_URL` and `WK_API_AUTH` in `wp-config.php`; each constant wins over its field on the settings page.

= Why does the Themes setting say there are no themes? =

The add-on hasn't fetched them yet. Check the connection under **Forms > Settings > Wereldkundig** and save the settings to fetch the themes again.

= How do I know a subscriber was sent? =

Open the entry. Its notes say whether the subscriber was sent, or why not.

== External services ==

This plugin connects to the Wereldkundig platform you configure under **Forms > Settings > Wereldkundig** (or with the `WK_API_URL` constant). It is needed to fetch your themes and to subscribe the people who fill in your forms. Every request carries the Basic Auth code you entered, and only goes over `https://`.

* **Fetching the themes and subscriber fields** (`GET /v1/subscribe/fields`): when you save the settings, and once an hour in the background while the platform is configured. No personal data is sent.
* **Subscribing** (`POST /v1/subscribe`): when a visitor submits a form with the Wereldkundig subscribe box checked. It sends the email address, the names and other fields mapped in the feed, and the chosen themes and categories. Nothing is sent when the box is not checked.

The service is provided by Wereldkundig: [wereldkundig.nl](https://wereldkundig.nl/), API documentation at [docs.wereldkundig.nl/api](https://docs.wereldkundig.nl/api).

== Changelog ==

= 1.0.0 =
* First release.

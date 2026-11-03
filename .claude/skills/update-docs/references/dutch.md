# Writing the Dutch docs

The Dutch pages are for Dutch site builders and editors (municipalities, organisations) who use Wereldkundig. They should read as written in Dutch, not as a word-for-word translation, while saying exactly what the English page says. Keep the structure the same (same headings in the same order, same lists and tables), so the two pages stay easy to compare and the language picker lands on the same content.

## Tone

- Informal **je**, like docs.wereldkundig.nl ("Hier vind je …"). Never **u**.
- Short, active sentences. Prefer "Sla de instellingen op" over "De instellingen dienen te worden opgeslagen".
- Headings in sentence case, like the English.
- English loanwords that Dutch WordPress users use anyway stay English: feed, cache, filter, hook, add-on, plugin, endpoint, slug, time-out, WP-Cron, loopback-verzoek.
- Compounds with a product name get a hyphen: Gravity Forms-add-on, Wereldkundig-instellingen, WP-Cron-schema, Gravity Forms-log.

## Terminology

Use these everywhere, so a term means one thing across the site.

| English | Dutch |
|---|---|
| theme / themes | thema / thema's (the platform's own word) |
| category, parent category, child category | categorie, hoofdcategorie, subcategorie |
| "Alles in …" | "Alles in …" (platform text, unchanged) |
| subscribe field | inschrijfveld (first mention on a page: het veld **Wereldkundig subscribe me**) |
| subscribe checkbox / box | inschrijfvinkje / vinkje |
| subscribe (verb) / subscription | inschrijven / inschrijving |
| subscriber, subscriber fields | abonnee, abonneevelden |
| entry, entry note | inzending, notitie (bij de inzending) |
| submit (the form) | versturen |
| feed, feed error | feed, feedfout |
| field mapping, map | veldkoppeling, koppelen |
| auto-detected | automatisch gevonden |
| background refresh, hourly refresh | verversing op de achtergrond, verversing van elk uur |
| fetch | ophalen |
| credentials | gegevens (platform-URL en Basic Auth-code) |
| request, response | verzoek, antwoord |
| capability | recht |
| settings page | instellingenpagina |
| form editor | formuliereditor |
| consent, GDPR | toestemming, AVG |
| WordPress theme (styling) | WordPress-thema (always with "WordPress-", to avoid confusion with Wereldkundig-thema's) |

## Screen labels

A Dutch admin sees two languages at once, and the docs follow what is on screen:

- **WordPress and Gravity Forms labels: Dutch.** Look them up instead of translating them yourself, so they match the screen:

  ```bash
  python3 .claude/skills/update-docs/scripts/gf_label.py "Form Settings" "System Status"
  ```

  Already used: Formulieren > Instellingen > Wereldkundig, Formulierinstellingen > Wereldkundig, Formulieren > Systeemstatus, Formulieren > Instellingen > Loggen, Formulieren > Instellingen > De-installeren, Standaardvelden, Instellingen opslaan, Formulier opslaan, Inzendingen, Notities, Zichtbaar / Verborgen / Administratief, Voorwaardelijke logica (as a concept), field types E-mailadres, Naam, Verborgen, Tekstregel. The WordPress screen **Plugins** is also Plugins in Dutch.

- **The add-on's own labels and texts: English**, because the add-on has no Dutch translation yet. Quote them exactly: **Wereldkundig subscribe me**, **Themes**, **Checked by default**, **Platform URL**, **Basic Auth code**, the feed settings **Name**, **Map Fields** and **Conditional Logic**, the editor notices, connection status messages, entry notes ("… was sent to Wereldkundig …"), error messages and **Yes** in entries. Explain them in Dutch around the quote.

When the add-on gets a Dutch translation (`languages/gravityformswereldkundig-nl_NL.po`), switch these to the Dutch strings and remove the note about English labels from `nl/getting-started/installation.md`.

## Links and anchors

- Prefix internal links with `/nl/`: `/nl/getting-started/connect/`.
- Anchors are generated from the Dutch heading: lowercase, spaces become hyphens, punctuation is dropped. `## Gegevens in wp-config.php` → `#gegevens-in-wp-configphp`. Rename a Dutch heading, and update every link to it; the build's link check will name any you miss.
- Hook anchors come from the hook names and are the same as in English: `/nl/reference/hooks/#wk_gf_auto_map_fields`.
- External links (docs.wereldkundig.nl, the API docs) stay as they are.

## Hook descriptions

`docs/src/data/hooks-translations/nl.json` translates the docblocks of the filters. Keep them as short as the English, in the same terminology; values such as `hourly`, `false`, hook names and endpoints stay as they are. `description` is shown as plain text; `long_description` may use `inline code` in backticks.

---
title: Opgeslagen gegevens
description: De opties, feedinstellingen en kenmerken die de add-on in WordPress opslaat.
---

## Opties

Elke site heeft zijn eigen.

| Optie | Inhoud |
|---|---|
| `gravityformsaddon_wereldkundig_settings` | De Gravity Forms-instellingen: `api_url` en `api_auth`. |
| `wk_gf_api_fields` | De thema's en abonneevelden in de cache: `key`, `fetched_at` en `response`. |
| `wk_gf_api_fields_error` | De laatste mislukte poging: `message`, `code`, `time` en `source` (`settings` of `cron`). Wordt verwijderd bij de volgende geslaagde poging. |

## Het inschrijfveld

Het veldtype is `wk_gf_subscribe`. De enige eigen instelling, `wk_gf_default_checked`, wordt in het formulier opgeslagen.

## Feeds

Een Wereldkundig-feed slaat deze op in zijn `meta`, naast de eigen `feedName`, `mapped_fields_*` en `feed_condition_*` van Gravity Forms:

- `subscribe_field`: het veld-ID van het inschrijfvinkje, het enige van het formulier als het er één heeft;
- `wk_gf_themes`: thema-ID => de categoriesleutels die in dat thema zijn aangevinkt, waarbij een lege lijst het hele thema betekent. Bijvoorbeeld `{"theme-1": [], "theme-2": ["category-36-37"]}`.

Alleen geldige thema- en categorie-ID's worden opgeslagen.

## Kenmerken

| | |
|---|---|
| Slug van de add-on | `wereldkundig`, bijvoorbeeld als `addon_slug` van een feed |
| WP-Cron-gebeurtenis | `wk_gf_refresh_fields` |
| Text domain | `wereldkundig-for-gravity-forms` |

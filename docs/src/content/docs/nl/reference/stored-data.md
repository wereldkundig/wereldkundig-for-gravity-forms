---
title: Opgeslagen gegevens
description: De opties, veldinstellingen en kenmerken die de add-on in WordPress opslaat.
---

## Opties

Elke site heeft zijn eigen.

| Optie | Inhoud |
|---|---|
| `gravityformsaddon_wereldkundig_settings` | De Gravity Forms-instellingen: `api_url` en `api_auth`. |
| `wk_gf_api_fields` | De thema's en abonneevelden in de cache: `key`, `fetched_at` en `response`. |
| `wk_gf_api_fields_error` | De laatste mislukte poging: `message`, `code`, `time` en `source` (`settings` of `cron`). Wordt verwijderd bij de volgende geslaagde poging. |

## Het inschrijfveld

Het veldtype is `wk_subscribe`. De instellingen worden in het formulier opgeslagen:

- `wk_theme_ids`: de ID's van de aangevinkte thema's;
- `wk_category_ids`: de categoriesleutels die in die thema's zijn aangevinkt.

Bij het opslaan van het formulier blijven alleen geldige ID's over.

## Kenmerken

| | |
|---|---|
| Slug van de add-on | `wereldkundig`, bijvoorbeeld als `addon_slug` van een feed |
| WP-Cron-gebeurtenis | `wk_gf_refresh_fields` |
| Text domain | `gravityformswereldkundig` |

---
title: Het inschrijfverzoek
description: De endpoints van het platform die de add-on gebruikt en wat hij verstuurt, voor ontwikkelaars die de filters gebruiken.
---

De add-on gebruikt twee endpoints van de Wereldkundig API. De volledige API staat beschreven op [docs.wereldkundig.nl/api](https://docs.wereldkundig.nl/api).

- Basis-URL: `{platform}/wp-json/wereldkundig`, aan te passen met [`wk_gf_api_base_url`](/nl/reference/hooks/#wk_gf_api_base_url).
- Elk verzoek stuurt de Basic Auth-header en JSON mee, en wacht maximaal 10 seconden ([`wk_gf_api_timeout`](/nl/reference/hooks/#wk_gf_api_timeout)).

## GET /v1/subscribe/fields

Geeft de abonneevelden en de **thema's** van het platform terug, elk met categorieën en subcategorieën. Dit antwoord is wat de add-on [in de cache bewaart](/nl/guides/themes-and-refresh/).

Thema-ID's worden altijd geschreven als `theme-{id}`: `1`, `theme-1` en `theme_1` worden allemaal `theme-1`.

## POST /v2/subscribe

Schrijft één persoon in voor alle gekozen thema's, in één verzoek:

```json
{
  "email": "jan@example.nl",
  "first_name": "Jan",
  "last_name": "Jansen",
  "upsert": false,
  "options": { "theme-1": true, "category-36-37": true, "category-36-38": true }
}
```

- `options` bevat een heel thema als `theme-{id}`, of alleen de gekozen categorieën van een thema met de categoriesleutels van het platform: `category-{id}`, `category-{parent}-{id}` voor een subcategorie, of `select-all-{id}` voor alles in een hoofdcategorie.
- Gekozen categorieën worden zonder hun thema verstuurd; het platform voegt het thema zelf toe. Zou je het thema ook meesturen, dan schrijf je in voor het hele thema.
- `email` wordt altijd verstuurd. Andere velden alleen als ze een waarde hebben, zodat een leeg veld geen waarde op het platform kan wissen.
- `upsert` komt uit de feedinstelling **Bestaande abonnees** (standaard uit). Bij `false` krijgt een e-mailadres dat al lid is van de platformsite `409 member-exists` en wordt er niets opgeslagen. Bij `true` wordt die abonnee bijgewerkt en worden de thema's erbij gemengd.
- Veldsleutels moeten voldoen aan `[a-z0-9_]+`. Waarden die geen tekst of getal zijn, worden weggelaten.
- Het antwoord van het platform bevat de `user_id` op het platform, `created` (false als een bestaande abonnee is bijgewerkt) en eventuele `rejected_options`. Die staan in de [notitie bij de inzending](/nl/reference/entry-notes-and-errors/#notities-bij-inzendingen).

De velden komen uit [`wk_gf_subscriber_pre_subscribe`](/nl/reference/hooks/#wk_gf_subscriber_pre_subscribe) en de thema's uit [`wk_gf_subscribe_themes`](/nl/reference/hooks/#wk_gf_subscribe_themes): thema-ID => categoriesleutels, waarbij een lege array het hele thema betekent.

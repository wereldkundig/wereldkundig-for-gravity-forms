---
title: Feeds en veldkoppeling
description: Waarom een formulier een Wereldkundig-feed nodig heeft, hoe velden worden gekoppeld, en hoe je velden in plaats daarvan automatisch laat vinden.
---

Een formulier heeft een feed nodig om abonnees te versturen: de veldkoppeling van de feed vertelt de add-on welk formulierveld het e-mailadres bevat. Een feed voegt ook voorwaardelijke logica en versturen op de achtergrond toe. Zonder feed werkt het inschrijfvinkje alleen als je [automatisch gevonden velden](#automatisch-gevonden-velden) aanzet.

| | Zonder feed | Met feed |
|---|---|---|
| Wanneer het wordt verstuurd | tijdens het versturen van het formulier | op de achtergrond, direct na het versturen |
| Veldkoppeling | geen: alleen met automatisch gevonden velden aan | per abonneeveld |
| Voorwaardelijke logica | die van het veld | die van het veld **en** de voorwaarde van de feed |
| Fouten | notitie bij de inzending | feedfout: een notitie bij de inzending en het Gravity Forms-log |

## Een feed toevoegen

Open het formulier en ga naar **Formulierinstellingen > Wereldkundig**, en voeg een feed toe. Dat kan zodra het [platform is gekoppeld](/nl/getting-started/connect/). Een feed heeft:

- **Name**: voor je eigen overzicht.
- **Map Fields**: welk formulierveld elk abonneeveld vult.
- **Conditional Logic**: staat dit aan, dan wordt de abonnee alleen verstuurd als de voorwaarde klopt. Het inschrijfvinkje moet nog steeds aangevinkt zijn.

Feeds kun je dupliceren.

## Velden koppelen

**Map Fields** toont elk abonneeveld dat het platform aanbiedt, zoals e-mailadres, voornaam, tussenvoegsel en achternaam. Geen ervan is verplicht, maar koppel in elk geval het e-mailadres: zonder e-mailadres wordt de abonnee niet verstuurd en krijgt de inzending de fout `wk_email_invalid`.

- Voor het e-mailadres kun je alleen formuliervelden van het type E-mailadres, Verborgen en Tekstregel kiezen.
- Uitgeschakelde onderdelen van een Naam-veld, zoals de aanhef of het achtervoegsel, worden niet aangeboden.
- Een koppeling die je zelf kiest, gaat altijd voor op de automatische herkenning hieronder, als die aanstaat.
- Een veld dat bij het versturen leeg is, wordt niet meegestuurd, dus het kan geen waarde wissen die al op het platform staat.

Kunnen de abonneevelden niet van het platform worden geladen, dan toont de feedpagina een foutmelding in plaats van de koppeling. Sla je de feed dan op, dan blijft de bestaande koppeling bewaard.

## Automatisch gevonden velden

Standaard worden velden niet automatisch gevonden. Zet het aan met het filter [`wk_gf_auto_map_fields`](/nl/reference/hooks/#wk_gf_auto_map_fields):

```php
add_filter('wk_gf_auto_map_fields', '__return_true');
```

Dan worden lege koppelingen, en formulieren zonder feed, bij het versturen aangevuld:

- **E-mailadres**: het eerste veld van het type E-mailadres.
- **Voornaam, tussenvoegsel en achternaam**: het eerste Naam-veld. Een eenvoudig Naam-veld vult alleen de voornaam.
- Zonder Naam-veld: het eerste veld van het type Tekstregel of Verborgen met het label *first name* / *voornaam*, *middle name* / *tussenvoegsel(s)*, en *last name* / *surname* / *achternaam*. Anders vult een veld met precies het label *name* of *naam* de voornaam.

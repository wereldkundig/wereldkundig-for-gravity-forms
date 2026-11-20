---
title: Feeds en veldkoppeling
description: Waarom een formulier een Wereldkundig-feed nodig heeft, hoe die het vinkje en de thema's kiest, hoe velden worden gekoppeld, en wat er gebeurt als een e-mailadres al bestaat.
---

Een formulier heeft een feed nodig om abonnees te versturen. De feed kiest welk inschrijfvinkje de toestemming geeft, voor welke thema's en categorieën wordt ingeschreven, en welk formulierveld het e-mailadres bevat. Een feed voegt ook voorwaardelijke logica toe, en verstuurt op de achtergrond, direct na het versturen. Zonder feed wordt er niets verstuurd, ook niet als het vinkje aan staat.

## Een feed toevoegen

Open het formulier en ga naar **Formulierinstellingen > Wereldkundig**, en voeg een feed toe. Dat kan zodra het [platform is gekoppeld](/nl/getting-started/connect/). Een feed heeft:

- **Naam**: voor je eigen overzicht.
- **Inschrijfvinkje**: het veld **Inschrijven bij Wereldkundig** van dit formulier dat de toestemming geeft. Alleen te kiezen als het formulier er meer dan één heeft; anders gebruikt de feed het enige.
- **Thema's**: de thema's en categorieën waarvoor wordt ingeschreven. Vink er minstens één aan.
- **Velden koppelen**: welk formulierveld elk abonneeveld vult.
- **Bestaande abonnees**: standaard uit. Of een e-mailadres dat al lid is van de platformsite wordt bijgewerkt; zie [Bestaande abonnees](#bestaande-abonnees).
- **Voorwaardelijke logica**: staat dit aan, dan wordt de abonnee alleen verstuurd als de voorwaarde klopt. Het hierboven gekozen inschrijfvinkje moet nog steeds aangevinkt zijn.

De lijst met feeds toont de naam en de thema's van elke feed. Feeds kun je dupliceren.

## Inschrijfvinkje

De feed verstuurt de abonnee alleen als zijn inschrijfvinkje is aangevinkt. Welk vinkje dat is, hangt af van het aantal velden **Inschrijven bij Wereldkundig** op het formulier:

- **Eén**: er valt niets te kiezen. De feed toont het veld met zijn label (of beheerderslabel) en veld-ID, gevolgd door "het enige inschrijfvinkje van dit formulier", en gebruikt het altijd. Ook een feed die is opgeslagen voordat het veld werd toegevoegd, of waarvan het gekozen veld door een ander is vervangen.
- **Meerdere**, bijvoorbeeld één per nieuwsbrief: **Inschrijfvinkje** is een verplichte lijst met die velden. Geef elk vinkje een eigen feed, met eigen thema's. Elke feed waarvan het vinkje aan staat, verstuurt een eigen verzoek. Wordt het gekozen veld later van het formulier verwijderd, dan krijgt de inzending de fout `wk_gf_subscribe_field_missing`; kies dan een ander vinkje in de feed.
- **Geen**: de feed toont in plaats daarvan een melding. Je kunt de feed toch opslaan; zodra je het veld in de formuliereditor toevoegt, gebruikt de feed het, zonder dat je de feed opnieuw hoeft te bewerken. Tot die tijd krijgen inzendingen de fout `wk_gf_subscribe_field_missing`.

Had het formulier één inschrijfveld en voeg je een tweede toe, dan houden bestaande feeds het vinkje waarmee ze zijn opgeslagen.

## Thema's en categorieën

**Thema's** toont een vinkje voor elk thema op het platform, met de categorieën van dat thema eronder.

- Een aangevinkt thema **zonder** aangevinkte categorieën wordt in zijn geheel ingeschreven.
- Vink categorieën aan om **alleen** voor die categorieën van het thema in te schrijven.
- Een hoofdcategorie staat er als **Alles in …** (alles daarin), met de subcategorieën ingesprongen eronder.
- Categorieën van een thema dat niet is aangevinkt, zijn uitgeschakeld en worden niet opgeslagen.

Sla je een feed op zonder aangevinkt thema, dan verschijnt **Vink minimaal één thema aan.**

De thema's komen uit de cache op je site, gevuld vanaf het platform. Een thema dat net op het platform is toegevoegd, verschijnt na de [volgende verversing](/nl/guides/themes-and-refresh/), of meteen als je de Wereldkundig-instellingen opslaat. Kunnen er geen thema's worden geladen, dan toont de feed **De thema's van Wereldkundig konden niet worden geladen.**, en blijven bij het opslaan van de feed de thema's bewaard die hij al had.

## Velden koppelen

**Velden koppelen** toont elk abonneeveld dat het platform aanbiedt, zoals e-mailadres, voornaam, tussenvoegsel en achternaam. Geen ervan is verplicht, maar koppel in elk geval het e-mailadres: zonder e-mailadres wordt de abonnee niet verstuurd en krijgt de inzending de fout `wk_gf_email_invalid`.

- Voor het e-mailadres kun je alleen formuliervelden van het type E-mailadres, Verborgen en Tekstregel kiezen.
- Uitgeschakelde onderdelen van een Naam-veld, zoals de aanhef of het achtervoegsel, worden niet aangeboden.
- Een koppeling die je zelf kiest, gaat altijd voor op de automatische herkenning hieronder, als die aanstaat.
- Een veld dat bij het versturen leeg is, wordt niet meegestuurd, dus het kan geen waarde wissen die al op het platform staat.

Kunnen de abonneevelden niet van het platform worden geladen, dan toont de feedpagina een foutmelding in plaats van de koppeling. Sla je de feed dan op, dan blijft de bestaande koppeling bewaard.

## Bestaande abonnees

**Bestaande abonnees** bepaalt wat er gebeurt als het e-mailadres al lid is van de platformsite. Standaard uit.

- **Uit**: het platform antwoordt met `member-exists` en slaat niets op, ook de nieuwe thema's niet. De inzending krijgt een [foutnotitie](/nl/reference/entry-notes-and-errors/).
- **Aan** (vink **Bijwerken als een e-mailadres al bestaat op het Wereldkundig-nieuwsbriefplatform** aan): die abonnee wordt bijgewerkt en de thema's van de feed worden gemengd met de thema's die ze al hebben. De notitie bij de inzending zegt dat de abonnee al bij Wereldkundig stond en is bijgewerkt.

Lege gekoppelde velden worden niet meegestuurd, dus een update kan geen naam of andere waarde wissen die al op het platform staat. Het inschrijfvinkje moet nog steeds aangevinkt zijn; deze instelling verandert alleen wat er gebeurt als het e-mailadres al bestaat.

De add-on stuurt dit als `upsert` in het [inschrijfverzoek](/nl/reference/subscribe-request/).

## Automatisch gevonden velden

Standaard worden velden niet automatisch gevonden. Zet het aan met het filter [`wk_gf_auto_map_fields`](/nl/reference/hooks/#wk_gf_auto_map_fields):

```php
add_filter('wk_gf_auto_map_fields', '__return_true');
```

Dan worden lege koppelingen bij het versturen aangevuld:

- **E-mailadres**: het eerste veld van het type E-mailadres.
- **Voornaam, tussenvoegsel en achternaam**: het eerste Naam-veld. Een eenvoudig Naam-veld vult alleen de voornaam.
- Zonder Naam-veld: het eerste veld van het type Tekstregel of Verborgen met het label *first name* / *voornaam*, *middle name* / *tussenvoegsel(s)*, en *last name* / *surname* / *achternaam*. Anders vult een veld met precies het label *name* of *naam* de voornaam.

---
title: Problemen oplossen
description: Wat je controleert als niemand wordt ingeschreven, en waar je ziet waarom.
---

## Er wordt niemand ingeschreven

1. Staat het inschrijfveld op **Zichtbaar**? Velden op Verborgen en Administratief worden niet verstuurd, zie [Zichtbaarheid](/nl/guides/subscribe-field/#zichtbaarheid).
2. Toont **Formulieren > Instellingen > Wereldkundig** de melding **Successfully connected**? Zo niet, sla de instellingen op en lees de foutmelding. Zie [Koppelen aan het platform](/nl/getting-started/connect/#verbindingsstatus).
3. Zijn de thema's van het veld nog aangevinkt in de formuliereditor? Thema's die van het platform zijn verwijderd, mislukken met **Invalid Wereldkundig theme**.
4. Met een feed: klopt de voorwaarde, en werken loopback-verzoeken onder **Formulieren > Systeemstatus**? Anders wachten feeds in de wachtrij op WP-Cron. Zie [Versturen op de achtergrond](/nl/guides/background-sending/#loopback-verzoeken).
5. Lees de notities bij de inzending en het Gravity Forms-log.

## Notities bij inzendingen

Elke inzending met een aangevinkt inschrijfvinkje krijgt een notitie die zegt of de abonnee is verstuurd, of waarom niet. De codes staan onder [Notities en foutmeldingen](/nl/reference/entry-notes-and-errors/).

## Loggen

Ga naar **Formulieren > Instellingen > Loggen** en zet **Wereldkundig** aan. Overgeslagen, mislukte en geslaagde inschrijvingen worden dan gelogd, ook inzendingen die zijn overgeslagen omdat er geen inschrijfvinkje aan stond.

## Veelvoorkomende problemen

**Een nieuw thema verschijnt niet in de formuliereditor.**
De editor gebruikt de thema's uit de cache. Sla de Wereldkundig-instellingen op om ze nu op te halen, of wacht op de [verversing van elk uur](/nl/guides/themes-and-refresh/).

**De notitie zegt `wk_email_invalid`.**
De abonnee heeft geen e-mailadres. Voeg een feed toe onder **Formulierinstellingen > Wereldkundig** en koppel het veld E-mailadres onder **Map Fields**, of zet [automatisch gevonden velden](/nl/guides/feeds-and-mapping/#automatisch-gevonden-velden) aan.

**De notitie zegt `member-exists`.**
Het e-mailadres is al lid van de platformsite, en het platform heeft niets opgeslagen. Voeg de thema's voor deze persoon op het platform zelf toe.

**Verzoeken lopen in een time-out.**
Het platform krijgt 10 seconden om te antwoorden. Verhoog dat met [`wk_api_timeout`](/nl/reference/hooks/#wk_api_timeout), of verstuur op de achtergrond met een feed, zodat bezoekers niet wachten.

**De verversing op de achtergrond is mislukt.**
De instellingenpagina toont wanneer en waarom. De thema's die je had, blijven werken; de volgende verversing, of het opslaan van de instellingen, probeert het opnieuw.

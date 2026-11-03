---
title: Beveiliging en privacy
description: Hoe de add-on omgaat met toestemming, de Basic Auth-code en de gegevens die hij verstuurt.
---

## Toestemming

- Er wordt niets verstuurd als de bezoeker het inschrijfvinkje niet heeft aangevinkt.
- **Checked by default** staat uit, en wie het aanzet, krijgt een AVG-waarschuwing. Zie [Standaard aangevinkt](/nl/guides/subscribe-field/#standaard-aangevinkt).

## Wat er wordt verstuurd

Het e-mailadres, en de namen en andere velden die zijn [gekoppeld](/nl/guides/feeds-and-mapping/), samen met de gekozen thema's. Lege velden worden niet verstuurd.

Naast de instellingen en de thema's in de cache slaat de add-on geen eigen gegevens op. De verstuurde gegevens blijven in de Gravity Forms-inzending, en de notities bij de inzending bevatten het e-mailadres. Als je het loggen van Gravity Forms aanzet, legt dat ook de inschrijvingen vast.

## De verbinding

- **Alleen https.** De Basic Auth-code gaat met elk verzoek mee, dus de platform-URL moet `https://` gebruiken. Dat wordt gecontroleerd bij het opslaan van de instellingen en opnieuw voor elk verzoek. Gewoon `http://` mag alleen voor `localhost`, `*.localhost` en `*.test`.
- **De code blijft verborgen.** De instellingenpagina toont alleen het begin van de Basic Auth-code, en debug-uitvoer kort hem in tot 4 tekens. Houd hem helemaal buiten de database met [constanten in wp-config.php](/nl/getting-started/connect/#gegevens-in-wp-configphp).
- **Versleuteling.** Gravity Forms 2.7.17 en nieuwer versleutelt de instellingen van add-ons als `GF_ENCRYPTION_KEY` is gedefinieerd.

## Invoer en uitvoer

- De thema's van een verstuurd veld worden voor het inschrijven gecontroleerd tegen de thema's van het platform, zodat gewijzigde of verouderde thema-ID's niet worden verstuurd.
- Verstuurde waarden worden opgeschoond, en namen en labels van het platform worden bij het tonen ge-escaped.

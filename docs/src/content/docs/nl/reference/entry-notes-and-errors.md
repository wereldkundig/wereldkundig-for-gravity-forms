---
title: Notities en foutmeldingen
description: Wat er gebeurt bij het versturen, de notities die een inzending krijgt, en elke foutcode met de oorzaak.
---

## Wat er gebeurt bij het versturen

Een formulier zonder Wereldkundig-feed verstuurt niets. Voor elke feed van het formulier waarvan de voorwaarde klopt:

1. Spam-inzendingen worden overgeslagen.
2. Het formulier heeft geen inschrijfveld, of meerdere en het **Inschrijfvinkje** van de feed hoort daar niet bij: de fout `wk_gf_subscribe_field_missing`. Een formulier met één inschrijfveld gebruikt altijd dat veld.
3. Dat vinkje staat niet aan: overgeslagen. Er komt geen notitie, het wordt alleen gelogd als [loggen](/nl/troubleshooting/#loggen) aanstaat.
4. De thema's van de feed worden gecontroleerd tegen de thema's van het platform, en alleen categorieën van dat thema blijven over.
5. De abonneevelden worden [gekoppeld](/nl/guides/feeds-and-mapping/), daarna draaien de filters [`wk_gf_subscribe_themes` en `wk_gf_subscriber_pre_subscribe`](/nl/reference/hooks/). Die kunnen de inschrijving aanpassen of overslaan.
6. Eén verzoek naar het platform, met alle thema's en categorieën van de feed.
7. De inzending krijgt een notitie met het resultaat.

## Notities bij inzendingen

Bij succes:

> **reader@example.com is naar Wereldkundig verstuurd (gebruikers-ID op het platform: 12). Thema's: Nieuwsbrief; Wijken (Noord, Zuid).**

De notitie noemt ook de opties die het platform heeft geweigerd.

Bij een fout:

> **reader@example.com is niet naar Wereldkundig verstuurd: {message} (code: {code})**

Fouten die al voor het verzoek worden gevonden, luiden **{message} (code: {code})**.

Een fout is een feedfout: die verschijnt als notitie bij de inzending, in het Gravity Forms-log en als feedstatus van de inzending.

## Fouten voor het verzoek

| Code | Melding | Oorzaak |
|---|---|---|
| `wk_gf_subscribe_field_missing` | Het inschrijfvinkje dat in de feed is gekozen, staat niet op het formulier; kies er een in de feedinstellingen. Abonnee niet verstuurd. | Het formulier heeft geen inschrijfveld: voeg er een toe. Of het heeft er meerdere, en het **Inschrijfvinkje** van de feed is van het formulier verwijderd of nooit gekozen: kies er een in de feed. |
| `wk_gf_themes_unavailable` | De Wereldkundig-thema's konden niet worden geladen; controleer de API-verbinding. Abonnee niet verstuurd. | Helemaal geen thema's: het platform is niet gekoppeld of niet bereikbaar. |
| `wk_gf_theme_invalid` | Ongeldig Wereldkundig-thema: theme-9. | De feed heeft een thema dat het platform niet (meer) heeft, of helemaal geen thema (**geen gekozen**). De geldige thema's worden wel ingeschreven, maar het resultaat telt als mislukt. |
| `wk_gf_email_invalid` | Geen geldig e-mailadres om in te schrijven. | De feed koppelt het e-mailadres niet (en [automatisch gevonden velden](/nl/guides/feeds-and-mapping/#automatisch-gevonden-velden) staan uit), of het e-mailveld is leeg of geen geldig adres. |

## Fouten van het platform

| Code | Oorzaak |
|---|---|
| `wk_gf_api_credentials_missing` | Geen platform-URL of Basic Auth-code. |
| `wk_gf_api_insecure_url` | De URL gebruikt geen `https://`. |
| `wk_gf_api_json_encode` | De abonneegegevens konden niet naar JSON worden omgezet, bijvoorbeeld door tekst die geen geldige UTF-8 is. Er is niets verstuurd. |
| `member-exists` | Het e-mailadres is al lid van de platformsite. Het platform slaat niets op, ook de nieuwe thema's niet. |
| De eigen foutcode van het platform, of `wk_gf_api_http` | Elk ander antwoord dat geen succes is. De notitie toont de melding van het platform als het die meestuurt. |
| `wk_gf_api_subscribe_failed` | Het platform antwoordde, maar bevestigde de inschrijving niet. |
| `wk_gf_api_json` | Het antwoord van het platform is geen geldige JSON. |
| `wk_gf_api_fields_invalid` | De thema's en abonneevelden konden niet uit het antwoord van het platform worden gelezen. |
| `http_request_failed` | WordPress kon het verzoek niet afronden, bijvoorbeeld door een time-out. |

Welke van deze op de achtergrond opnieuw worden geprobeerd, staat onder [Mislukte feeds opnieuw proberen](/nl/guides/background-sending/#mislukte-feeds-opnieuw-proberen).

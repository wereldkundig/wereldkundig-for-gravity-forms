---
title: Notities en foutmeldingen
description: Wat er gebeurt bij het versturen, de notities die een inzending krijgt, en elke foutcode met de oorzaak.
---

## Wat er gebeurt bij het versturen

Voor een formulier met of zonder feed:

1. Spam-inzendingen worden overgeslagen.
2. Geen inschrijfveld op het formulier, of geen enkel aangevinkt: overgeslagen. Er komt geen notitie, het wordt alleen gelogd als [loggen](/nl/troubleshooting/#loggen) aanstaat.
3. De thema's van elk aangevinkt veld worden gecontroleerd tegen de thema's van het platform, en alleen categorieën van dat thema blijven over.
4. De abonneevelden worden [gekoppeld](/nl/guides/feeds-and-mapping/), daarna draaien de filters [`wk_gf_subscribe_themes` en `wk_gf_subscriber_pre_subscribe`](/nl/reference/hooks/). Die kunnen de inschrijving aanpassen of overslaan.
5. Eén verzoek naar het platform, met alle thema's en categorieën.
6. De inzending krijgt een notitie met het resultaat.

## Notities bij inzendingen

De notities komen van de add-on en zijn daarom in het Engels. Bij succes:

> **reader@example.com was sent to Wereldkundig (platform user ID 12). Themes: Nieuwsbrief; Wijken (Noord, Zuid).**

De notitie noemt ook de opties die het platform heeft geweigerd.

Bij een fout:

> **reader@example.com was not sent to Wereldkundig: {message} (code: {code})**

Fouten die al voor het verzoek worden gevonden, luiden **{message} (code: {code})**.

Met een feed is een fout een feedfout: die verschijnt als notitie bij de inzending, in het Gravity Forms-log en als feedstatus van de inzending. Zonder feed is het een notitie bij de inzending.

## Fouten voor het verzoek

| Code | Melding | Oorzaak |
|---|---|---|
| `wk_themes_unavailable` | Could not load the Wereldkundig themes; check the API connection. Subscriber not sent. | Helemaal geen thema's: het platform is niet gekoppeld of niet bereikbaar. |
| `wk_theme_invalid` | Invalid Wereldkundig theme: theme-9. | Een aangevinkt veld heeft een thema dat het platform niet (meer) heeft, of helemaal geen thema. De geldige thema's worden wel ingeschreven, maar het resultaat telt als mislukt. |
| `wk_email_invalid` | No valid email address to subscribe. | Geen feed koppelt het e-mailadres (en [automatisch gevonden velden](/nl/guides/feeds-and-mapping/#automatisch-gevonden-velden) staan uit), of het e-mailveld is leeg of geen geldig adres. |

## Fouten van het platform

| Code | Oorzaak |
|---|---|
| `wk_api_credentials_missing` | Geen platform-URL of Basic Auth-code. |
| `wk_api_insecure_url` | De URL gebruikt geen `https://`. |
| `wk_api_json_encode` | De abonneegegevens konden niet naar JSON worden omgezet, bijvoorbeeld door tekst die geen geldige UTF-8 is. Er is niets verstuurd. |
| `member-exists` | Het e-mailadres is al lid van de platformsite. Het platform slaat niets op, ook de nieuwe thema's niet. |
| De eigen foutcode van het platform, of `wk_api_http` | Elk ander antwoord dat geen succes is. De notitie toont de melding van het platform als het die meestuurt. |
| `wk_api_subscribe_failed` | Het platform antwoordde, maar bevestigde de inschrijving niet. |
| `wk_api_json` | Het antwoord van het platform is geen geldige JSON. |
| `wk_api_fields_invalid` | De thema's en abonneevelden konden niet uit het antwoord van het platform worden gelezen. |
| `http_request_failed` | WordPress kon het verzoek niet afronden, bijvoorbeeld door een time-out. |

Welke van deze op de achtergrond opnieuw worden geprobeerd, staat onder [Mislukte feeds opnieuw proberen](/nl/guides/background-sending/#mislukte-feeds-opnieuw-proberen).

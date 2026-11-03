---
title: Versturen op de achtergrond
description: Hoe feeds na het versturen worden afgehandeld, wat dat van je server vraagt, en hoe je het opnieuw probeert of uitzet.
---

Formulieren met een Wereldkundig-feed versturen de abonnee op de achtergrond, zodat de bezoeker nooit op het platform wacht. Gravity Forms regelt dit zelf:

1. Bij het versturen zet Gravity Forms de feed in een wachtrij, in plaats van hem meteen uit te voeren.
2. De bezoeker ziet direct de bevestiging.
3. Aan het eind van het verzoek stuurt Gravity Forms een verzoek naar zijn eigen site (een loopback-verzoek). Dat verstuurt de abonnee en voegt de notitie bij de inzending toe.
4. Een WP-Cron-controle, elke 5 minuten, pakt alles op wat het loopback-verzoek heeft gemist.

Een formulier **zonder** Wereldkundig-feed schrijft nog steeds in tijdens het versturen, want de wachtrij van Gravity Forms verwerkt alleen feeds.

## Loopback-verzoeken

Versturen op de achtergrond werkt alleen als loopback-verzoeken werken; kijk bij **Formulieren > Systeemstatus**. Ze mislukken als de site achter HTTP Basic Auth staat, achter sommige firewalls, of bij een kapotte SSL-instelling. Feeds in de wachtrij wachten dan op WP-Cron, en dat draait alleen als de site bezoekers krijgt.

## Mislukte feeds opnieuw proberen

Een mislukte feed wordt standaard niet opnieuw geprobeerd. Verhoog het Gravity Forms-filter `gform_max_async_feed_attempts` om dat wel te doen:

```php
add_filter('gform_max_async_feed_attempts', function () {
    return 3;
});
```

Fouten die een nieuwe poging niet oplost, worden nooit opnieuw geprobeerd: een ongeldig thema of e-mailadres, ontbrekende gegevens of een `http://`-URL, abonneegegevens die niet naar JSON kunnen worden omgezet, en elk `4xx`-antwoord behalve `408` en `429`. Time-outs, `5xx`-antwoorden en thema's die niet konden worden geladen, worden wel opnieuw geprobeerd. Zie [Notities en foutmeldingen](/nl/reference/entry-notes-and-errors/).

## Toch versturen tijdens het versturen van het formulier

Wil je tijdens het versturen inschrijven, voor alle formulieren of voor één formulier of feed, gebruik dan het Gravity Forms-filter `gform_is_feed_asynchronous`:

```php
add_filter('gform_is_feed_asynchronous', function ($async, $feed) {
    if ('wereldkundig' === rgar($feed, 'addon_slug')) {
        return false;
    }

    return $async;
}, 10, 2);
```

De bezoeker wacht dan op het antwoord van het platform, tot de [time-out van het verzoek](/nl/reference/hooks/#wk_api_timeout).

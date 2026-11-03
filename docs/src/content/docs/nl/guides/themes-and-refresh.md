---
title: Thema's en de verversing elk uur
description: Waar de thema's in de formuliereditor vandaan komen, wanneer ze worden opgehaald, en hoe je ze ververst.
---

De thema's, hun categorieën en de abonneevelden komen in één verzoek van het platform. Je site bewaart ze in een cache, zodat de formuliereditor nooit op het platform wacht.

## Wanneer het platform wordt benaderd

| Waar | Gebruikt de cache | Benadert het platform als er niets in de cache staat |
|---|---|---|
| Opslaan van **Formulieren > Instellingen > Wereldkundig** | nee, haalt altijd op | ja |
| De verversing op de achtergrond, elk uur | nee, haalt altijd op | ja |
| De formuliereditor: de instelling **Themes** | ja | **nee** |
| De instellingenpagina: verbindingsstatus | ja | **nee** |
| **Map Fields** van een feed | ja | ja |
| Een inzending: de thema's controleren | ja | ja |

De formuliereditor benadert het platform nooit. Gravity Forms bouwt de editor opnieuw op na elke keer dat je een formulier opslaat, dus een traag of onbereikbaar platform zou anders het opslaan van elk formulier vertragen.

De cache hoort bij de platform-URL en de Basic Auth-code: verander je een van beide, dan worden de thema's opnieuw opgehaald. Een mislukte poging vervangt de cache nooit, dus de thema's die je had, blijven werken.

## De verversing op de achtergrond

De cache verloopt niet vanzelf. Hij wordt ververst:

- op de achtergrond door WP-Cron, elk uur, zodat nieuwe thema's binnen het uur verschijnen;
- meteen als je **de Wereldkundig-instellingen opslaat**.

De verversing wordt ingepland zolang het platform is gekoppeld, en verwijderd als de gegevens worden verwijderd, of als de add-on wordt gedeactiveerd of verwijderd. De eerste keer is een uur nadat hij is ingepland.

Verander de interval met de naam van een WP-Cron-schema. Onbekende namen vallen terug op elk uur:

```php
add_filter('wk_gf_refresh_fields_recurrence', function () {
    return 'twicedaily';
});
```

Verander de wachttijd voor de eerste keer, in seconden, met [`wk_gf_refresh_fields_initial_delay`](/nl/reference/hooks/#wk_gf_refresh_fields_initial_delay).

## WP-Cron

WP-Cron draait alleen als de site bezoekers krijgt. Op een site met weinig verkeer, of met `DISABLE_WP_CRON`, laat je de server `wp-cron.php` aanroepen, of draai je `wp cron event run --due-now`.

De verversing met de hand starten met WP-CLI:

```bash
wp cron event run wk_gf_refresh_fields
# Op multisite, per site:
wp cron event run wk_gf_refresh_fields --url=example.nl/site
```

## Een thema is verwijderd op het platform

Een inschrijfveld waarin een verwijderd thema nog is aangevinkt, mislukt bij het versturen met **Invalid Wereldkundig theme**. De andere thema's van het veld worden wel ingeschreven. Open het formulier, controleer de thema's van het veld en sla het formulier op.

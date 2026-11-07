---
title: Installatie
description: Vereisten, de add-on installeren en activeren, en weer verwijderen.
---

## Vereisten

- WordPress 6.9 of nieuwer
- PHP 8.2 of nieuwer
- Gravity Forms 2.5 of nieuwer
- Een Wereldkundig-platform, en de Basic Auth-code die Wereldkundig daarvoor levert

De add-on werkt op losse sites en op multisite-netwerken. In een netwerk heeft elke site zijn eigen instellingen, feeds en thema's.

## Installeren en activeren

1. Zet de map `wereldkundig-for-gravity-forms` in `wp-content/plugins/`, of upload de zip van de plugin op het scherm **Plugins**.
2. Activeer de plugin. Op multisite activeer je hem voor het hele netwerk.
3. Ga verder met [Koppelen aan het platform](/nl/getting-started/connect/).

De add-on laadt alleen als Gravity Forms actief is.

:::note
De schermen van WordPress en Gravity Forms staan in deze documentatie met hun Nederlandse namen, zoals **Formulieren > Instellingen**. De add-on zelf is nog niet vertaald: zijn eigen labels en meldingen, zoals **Wereldkundig subscribe me** en **Map Fields**, zie je in het Engels.
:::

## Deactiveren en verwijderen

- **Deactiveren** stopt de verversing op de achtergrond van de thema's op de huidige site. Na het deactiveren voor het netwerk houden de andere sites hun geplande verversing, die dan niets meer doet.
- **Verwijderen** via **Formulieren > Instellingen > De-installeren** verwijdert de instellingen en de feeds, de thema's in de cache, de opgeslagen foutmelding en de geplande verversing. Daarvoor heb je het [recht](/nl/reference/capabilities/) `gravityforms_wereldkundig_uninstall` nodig.

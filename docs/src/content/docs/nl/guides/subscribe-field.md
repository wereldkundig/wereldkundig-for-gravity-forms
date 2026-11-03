---
title: Het inschrijfveld
description: Het vinkje Wereldkundig subscribe me, de thema's en categorieën ervan, en de instellingen die tellen voor toestemming.
---

Het veld **Wereldkundig subscribe me**, in de groep **Standaardvelden**, voegt één vinkje toe aan een formulier. Vinkt een bezoeker het aan en verstuurt die het formulier, dan wordt die ingeschreven voor de thema's die voor dat veld zijn gekozen.

- Het veldlabel is het label van het vinkje; er staat geen apart label boven.
- Het ondersteunt de gebruikelijke veldinstellingen: beschrijving, CSS-klasse, verplicht, zichtbaarheid, beheerderslabel, plaatsing van het label en voorwaardelijke logica.
- De HTML is gelijk aan die van het eigen selectievakjesveld van Gravity Forms, dus je WordPress-thema geeft het dezelfde opmaak.
- Inzendingen en exports tonen **Yes** als het vinkje aan stond, en anders niets.

## Thema's en categorieën

De instelling **Themes** toont een vinkje voor elk thema op het platform, met de categorieën van dat thema eronder.

- Een aangevinkt thema **zonder** aangevinkte categorieën wordt in zijn geheel ingeschreven.
- Vink categorieën aan om **alleen** voor die categorieën van het thema in te schrijven.
- Een hoofdcategorie staat er als **Alles in …** (alles daarin), met de subcategorieën ingesprongen eronder.
- Categorieën van een thema dat niet is aangevinkt, zijn uitgeschakeld en worden niet opgeslagen.

Eén veld kan voor meerdere thema's tegelijk inschrijven. Een formulier kan ook meerdere inschrijfvelden hebben, bijvoorbeeld één vinkje per nieuwsbrief. Alle aangevinkte velden worden samen verstuurd, in één verzoek. Kiezen meerdere velden hetzelfde thema, dan wint een veld dat het hele thema kiest; anders tellen hun categorieën op.

De thema's komen uit de cache op je site, niet rechtstreeks van het platform. Een thema dat net op het platform is toegevoegd, verschijnt na de [volgende verversing](/nl/guides/themes-and-refresh/), of meteen als je de Wereldkundig-instellingen opslaat.

Staan er geen thema's in de cache, dan toont de instelling een rode melding:

- **Configure Wereldkundig API settings**: er zijn nog geen gegevens ingevuld, zie [Koppelen aan het platform](/nl/getting-started/connect/).
- **No Wereldkundig themes available**: de gegevens zijn ingevuld, maar er zijn geen thema's opgehaald. Sla de Wereldkundig-instellingen op en bekijk de verbindingsstatus.

Gebruikers die de Wereldkundig-instellingen mogen openen, krijgen de melding als link naar de instellingenpagina.

## Standaard aangevinkt

**Checked by default** toont het vinkje al aangevinkt als het formulier laadt. Standaard staat dit uit.

:::caution
Een vooraf aangevinkt vakje is onder de AVG geen geldige toestemming (Hof van Justitie van de EU, *Planet49*). Zet dit alleen aan als bezoekers verwachten dat ze worden ingeschreven door het formulier te versturen. De formuliereditor toont dezelfde waarschuwing.
:::

- Het geldt alleen voor een vers formulier. Na een validatiefout, of als de bezoeker een pagina teruggaat, staat de eigen keuze van de bezoeker er, dus een uitgevinkt vakje blijft uit.
- Een inzending staat alleen als aangevinkt als de bezoeker het vakje echt heeft aangevinkt.

## Zichtbaarheid

Laat het veld op **Zichtbaar** staan. Een veld op **Verborgen** of **Administratief** wordt niet door de bezoeker verstuurd, dus de waarde is leeg en er wordt niemand ingeschreven.

---
title: Het inschrijfveld
description: Het vinkje Wereldkundig subscribe me, hoe het bij een feed hoort, en de instellingen die tellen voor toestemming.
---

Het veld **Inschrijven bij Wereldkundig**, in de groep **Standaardvelden**, voegt één vinkje toe aan een formulier. Vinkt een bezoeker het aan en verstuurt die het formulier, dan wordt die ingeschreven voor de thema's van de [feed](/nl/guides/feeds-and-mapping/) die dit vinkje heeft gekozen.

- Het veldlabel is het label van het vinkje; er staat geen apart label boven.
- Het ondersteunt de gebruikelijke veldinstellingen: beschrijving, CSS-klasse, verplicht, zichtbaarheid, beheerderslabel, plaatsing van het label en voorwaardelijke logica.
- De HTML is gelijk aan die van het eigen selectievakjesveld van Gravity Forms, dus je WordPress-thema geeft het dezelfde opmaak.
- Inzendingen en exports tonen **Ja** als het vinkje aan stond, en anders niets.

## Thema's

Het veld zelf heeft geen thema's. Die kies je in een [Wereldkundig-feed](/nl/guides/feeds-and-mapping/#themas-en-categorieën) van het formulier. Heeft het formulier meer dan één inschrijfveld, dan kiest de feed ook het vinkje waar hij bij hoort, en tonen de veldinstellingen een melding met een link naar de feeds van het formulier. Met één inschrijfveld gebruikt de feed dat veld.

Een formulier kan meerdere inschrijfvelden hebben, bijvoorbeeld één vinkje per nieuwsbrief. Voeg dan voor elk vinkje een feed toe, met eigen thema's. Zonder feed verstuurt een aangevinkt vakje niets.

## Standaard aangevinkt

**Standaard aangevinkt** toont het vinkje al aangevinkt als het formulier laadt. Standaard staat dit uit.

:::caution
Een vooraf aangevinkt vakje is onder de AVG geen geldige toestemming (Hof van Justitie van de EU, *Planet49*). Zet dit alleen aan als bezoekers verwachten dat ze worden ingeschreven door het formulier te versturen. De formuliereditor toont dezelfde waarschuwing.
:::

- Het geldt alleen voor een vers formulier. Na een validatiefout, of als de bezoeker een pagina teruggaat, staat de eigen keuze van de bezoeker er, dus een uitgevinkt vakje blijft uit.
- Een inzending staat alleen als aangevinkt als de bezoeker het vakje echt heeft aangevinkt.

## Zichtbaarheid

Laat het veld op **Zichtbaar** staan. Een veld op **Verborgen** of **Administratief** wordt niet door de bezoeker verstuurd, dus de waarde is leeg en er wordt niemand ingeschreven.

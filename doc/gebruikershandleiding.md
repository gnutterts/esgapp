# Gebruikershandleiding

## Inhoudsopgave

1. [Inloggen](#inloggen)
2. [Speler Dashboard](#speler-dashboard)
3. [Publieke Pagina's](#publieke-paginas)
   - [Homepage](#homepage)
   - [Stand](#stand)
   - [Indeling](#indeling)
   - [Uitslag](#uitslag)
   - [Ratings](#ratings)

---

## Inloggen

De applicatie gebruikt een **wachtwoordloos login** systeem via een e-mailcode.

1. Ga naar de inlogpagina
2. Vul je e-mailadres in
3. Klik op **"Stuur inlogcode"**
4. Controleer je e-mail — je ontvangt een **6-cijferige code** die **15 minuten geldig** is
5. Voer de code in op de verificatiepagina om in te loggen

**Belangrijk:**
- Als je e-mailadres niet bekend is, zie je een foutmelding op de inlogpagina. Neem in dat geval contact op met de wedstrijdleider.
- Elke nieuwe aanvraag maakt eerdere ongebruikte codes ongeldig — gebruik altijd de nieuwste code.
- Er geldt een limiet van maximaal 5 inlogpogingen per minuut.

**Foutmeldingen:**
- *"Dit e-mailadres is niet bekend in ons systeem."* — Je e-mailadres staat niet in de ledenlijst. Neem contact op met de wedstrijdleider.
- *"Deze code is ongeldig of verlopen."* — De code is ouder dan 15 minuten, al gebruikt, of vervangen door een nieuwere aanvraag. Vraag een nieuwe aan.

---

## Speler Dashboard

Na het inloggen kom je op je persoonlijke dashboard.

### Overzicht

Het dashboard toont:
- **Jouw stand**: Je huidige positie en puntentotaal in de competitie, met een link naar de volledige stand
- **Huidige periode**: Welke periode (1-4) actief is en welk indelingssysteem (Swiss of Keizer) wordt gebruikt
- **Instellingen**: Toggles voor auto-deelname en ratingzichtbaarheid, plus je huidige ELO-rating
- **Komende rondes**: Tabel met datum, rondenummer, periode, deadline, status en je beschikbaarheid
- **Recente rondes**: Afgelopen rondes met je tegenstander, kleur, persoonlijk resultaat en behaalde punten

### Registratie voor rondes

- Klik op de groene/rode knop naast een ronde om je beschikbaarheid te wisselen
- **Groen (Beschikbaar)**: Je doet mee aan deze ronde
- **Rood (Niet beschikbaar)**: Je doet niet mee
- **Grijs (Niet opgegeven)**: Je hebt je nog niet aangemeld en auto-deelname staat uit. Je wordt in dit geval niet meegenomen bij de indeling.
- Registratie is alleen mogelijk als de ronde status "gepland" is en een eventuele deadline niet is verstreken
- Als er een registratie-deadline is ingesteld, kan je beschikbaarheid na dat moment niet meer worden gewijzigd

### Auto-deelname

Als auto-deelname is ingeschakeld, ben je automatisch beschikbaar voor alle rondes waar de inschrijving nog open is — zonder dat je je hoeft aan te melden. Op het dashboard zie je dan **"Beschikbaar (auto)"** in het groen.

- **Afmelden voor een specifieke ronde**: Klik op de knop naast de ronde om je af te melden. Je status wordt dan "Niet beschikbaar".
- **Weer beschikbaar maken**: Klik nogmaals op de knop. Je keert terug naar de automatische beschikbaarheid.
- **Auto-deelname uitschakelen**: Als je auto-deelname uitschakelt, ben je direct niet meer automatisch beschikbaar voor open rondes. Eventuele eerdere afmeldingen blijven staan.

Wanneer de registratie voor een ronde wordt gesloten, worden alle spelers met auto-deelname die zich niet hebben afgemeld definitief als "beschikbaar" geregistreerd.

### Rating

Het dashboard toont je huidige **ELO-rating** en, als je een KNSB-relatienummer hebt, een link naar je profiel op ratingviewer.nl.

Met de **toggle naast "Rating"** bepaal je of je rating zichtbaar is op de publieke [Ratings-pagina](#ratings):
- **Aan (groen)**: Je verschijnt op de publieke ratinglijst
- **Uit**: Je staat niet op de publieke ratinglijst

De wedstrijdleider beheert je ELO-rating en eventueel KNSB-relatienummer.

### Recente rondes — Puntenkolom

In de tabel met recente rondes staat een kolom **Punten**. Dit zijn de Keizer-punten die je in die specifieke ronde hebt verdiend (niet je cumulatieve totaal). Zie de [documentatie over het Keizer puntensysteem](/documentatie/puntensysteem) voor een uitleg van hoe deze punten worden berekend.

---

## Publieke Pagina's

Deze pagina's zijn voor iedereen toegankelijk (geen login vereist). Je vindt ze in het menu bovenaan de pagina.

### Homepage

Toont:
- Top 10 van de actuele stand (als er een seizoen actief is)
- Informatie over de volgende ronde

### Stand

Volledige standenlijst met alle kolommen:

| Afkorting | Betekenis |
|---|---|
| Pos | Positie |
| +/- | Positieverandering t.o.v. vorige ronde |
| Punten | Totaal Keizer-punten |
| p | Aantal gespeelde partijen |
| k | Kleurbalans (positief = meer wit, negatief = meer zwart) |
| g | Gewonnen partijen |
| r | Remise |
| v | Verloren partijen |
| e | Externe partijen |
| o | Vrij (bye) |
| a | Afwezig |

**Eindstand**: Aan het einde van het seizoen verschijnt naast de volledige stand ook een aparte **Eindstand**. Hierin staan alleen spelers die minimaal 7 keer hebben meegedaan. Spelers die minder dan 7 keer hebben deelgenomen tellen niet mee voor de officiële eindrangschikking.

### Indeling

Toont de bordindeling voor een specifieke ronde:
- Tabel met Bord, Wit en Zwart
- Eventuele vrije speler (bye) wordt apart vermeld
- Alleen zichtbaar nadat de indeling definitief is gemaakt
- **Navigatie**: Met de knoppen "Vorige ronde" en "Volgende ronde" kun je eenvoudig tussen rondes bladeren

Via het menu kun je ook direct naar de **laatst gepubliceerde indeling** gaan.

### Uitslag

Toont na afloop van een ronde:
1. **Resultaten**: Bord, Wit, Zwart, Uitslag (1-0, 0-1, 1/2-1/2)
2. **Stand na ronde**: Volledige standenlijst na deze ronde
3. **Eindstand** (alleen bij de laatste ronde van het seizoen): Gefilterde stand met alleen spelers die minimaal 7 keer hebben meegedaan
- **Navigatie**: Met de knoppen "Vorige ronde" en "Volgende ronde" kun je tussen rondes bladeren

Als er nog onbevestigde externe partijen zijn, wordt de stand als **"voorlopig"** gemarkeerd.

Via het menu kun je ook direct naar de **laatst afgeronde ronde** gaan.

### Ratings

De ratingpagina toont een overzicht van alle spelers die hun rating publiek hebben gemaakt, gesorteerd op ELO-rating:
- Rang, naam, ELO-rating en KNSB-relatienummer (klikbaar naar ratingviewer.nl)
- Klik op een naam om de detailpagina van die speler te openen

Op de **detailpagina** zie je:
- De huidige ELO-rating
- Een grafiek met de ratinghistorie per maand (afkomstig van de KNSB)
- Een link naar het profiel op ratingviewer.nl (als er een KNSB-nummer bekend is)

Spelers die hun rating niet publiek willen tonen, verschijnen niet op deze pagina. Je kunt je eigen zichtbaarheid beheren via de [rating-toggle op het dashboard](#rating).

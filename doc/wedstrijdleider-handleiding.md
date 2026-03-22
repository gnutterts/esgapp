# Wedstrijdleider Handleiding

## Inhoudsopgave

1. [Overzicht](#overzicht)
2. [Seizoenbeheer](#seizoenbeheer)
3. [Spelerbeheer](#spelerbeheer)
4. [Rondebeheer](#rondebeheer)
5. [Ronde workflow](#ronde-workflow)
6. [Indeling aanpassen](#indeling-aanpassen)
7. [Resultaten invoeren](#resultaten-invoeren)
8. [Externe partijen](#externe-partijen)
9. [Herberekening en standen](#herberekening-en-standen)
10. [ELO-ratings en KNSB-update](#elo-ratings-en-knsb-update)
11. [Instellingen](#instellingen)

---

## Overzicht

Als wedstrijdleider beheer je de volledige interne competitie: seizoenen, spelers, rondes, indelingen en resultaten. Het beheerpaneel is bereikbaar via **Beheer** in het menu (alleen zichtbaar als je bent ingelogd als wedstrijdleider).

---

## Seizoenbeheer

### Nieuw seizoen aanmaken

1. Ga naar **Beheer** > **Seizoenen** > **Nieuw seizoen**
2. Vul in:
   - **Naam** (bijv. "2025-2026")
   - **Startdatum**
   - **Einddatum** (moet na de startdatum liggen)
3. Klik op **Opslaan**

Bij het aanmaken wordt automatisch:
- Het vorige seizoen als niet-actief gemarkeerd
- 4 perioden aangemaakt:

| Periode | Rondes | Indelingssysteem |
|---|---|---|
| Periode 1 | 1 t/m 6 | Swiss |
| Periode 2 | 7 t/m 12 | Keizer |
| Periode 3 | 13 t/m 18 | Keizer |
| Periode 4 | 19 t/m 24 | Keizer |

De standaard opzet is 6 rondes per periode, 24 rondes per seizoen. Het aantal rondes per periode is niet strikt begrensd — rondenummers worden automatisch berekend op basis van bestaande rondes in de periode.

**Let op**: Rondes worden niet automatisch aangemaakt. Dit doe je handmatig per ronde (zie [Rondebeheer](#rondebeheer)).

### Seizoen bekijken

Op de seizoenpagina zie je alle perioden met hun rondes en de huidige status van elke ronde.

### Seizoen bewerken

1. Ga naar **Beheer** > **Seizoenen** en klik op het seizoen dat je wilt bewerken
2. Klik op **"Seizoen bewerken"**
3. Pas aan:
   - **Naam** (bijv. "2025-2026")
   - **Startdatum**
   - **Einddatum** (moet na de startdatum liggen)
4. Klik op **Opslaan**

De einddatum moet na de startdatum liggen; het systeem valideert dit bij opslaan.

### Seizoen verwijderen

1. Ga naar **Beheer** > **Seizoenen** en klik op het seizoen dat je wilt verwijderen
2. Klik op **"Verwijderen"** (op de seizoenspagina of in de seizoenenlijst)
3. Bevestig de melding

**Let op**: Verwijderen is onomkeerbaar. Alle bijbehorende gegevens worden ook verwijderd:
- Alle periodes van het seizoen
- Alle rondes binnen die periodes
- Alle indelingen, uitslagen en inschrijvingen

Als het verwijderde seizoen het actieve seizoen was, wordt automatisch het meest recente overgebleven seizoen als actief ingesteld.

---

## Spelerbeheer

### Speler toevoegen

1. Ga naar **Beheer** > **Spelers** > **Nieuwe speler**
2. Vul in:
   - **Naam** (verplicht)
   - **E-mailadres** (verplicht, moet uniek zijn)
   - **ELO-rating** (optioneel, 0-3000)
   - **KNSB-relatienummer** (optioneel, moet uniek zijn)
   - **Auto-deelname** (optioneel, standaard uit)
   - **Rating tonen** (optioneel, standaard aan — bepaalt of de speler op de publieke ratingpagina (KNSB) verschijnt)
3. Klik op **Opslaan**

De speler wordt aangemaakt met:
- Rol: **speler**
- Status: **actief**

Als je een KNSB-relatienummer invult, worden automatisch historische KNSB-ratings opgehaald (terug tot februari 2023). Dit gebeurt op de achtergrond.

### Speler bewerken

Op de bewerkpagina kun je aanpassen:
- Naam en e-mailadres
- ELO-rating (handmatige wijziging wordt vastgelegd in de ratinghistorie)
- KNSB-relatienummer (bij wijziging worden nieuwe historische ratings opgehaald)
- Auto-deelname in/uitschakelen
- Ratingzichtbaarheid (of de speler op de publieke ratingpagina verschijnt)

### Speler activeren/deactiveren

Klik op de **toggle-knop** naast een speler om deze actief of inactief te maken. Inactieve spelers:
- Worden niet meegenomen bij auto-deelname
- Kunnen zich niet aanmelden voor rondes

---

## Rondebeheer

### Nieuwe ronde aanmaken

1. Ga naar **Beheer** > **Rondes** > **Nieuwe ronde**
2. Selecteer de **periode** waartoe de ronde behoort
3. Vul in:
   - **Datum** (speeldatum)
   - **Registratie-deadline** (optioneel — tot wanneer spelers zich kunnen aan-/afmelden)
4. Klik op **Ronde aanmaken**

Het rondenummer binnen de periode (1-6) en het seizoensrondenummer (1-24) worden automatisch berekend.

**Let op**: De periode moet bij het huidige actieve seizoen horen.

**Zonder deadline**: Als je geen deadline invult, kunnen spelers zich aan- en afmelden totdat jij de inschrijving handmatig sluit. In het rondeoverzicht staat dan "Geen deadline (handmatig sluiten)".

### Ronde-overzicht

Op de rondepagina zie je:
- Status van de ronde
- Alle registraties (beschikbaar/niet beschikbaar)
- Bij geplande rondes: ook de auto-deelname spelers die nog geen expliciete registratie hebben
- De indeling (als deze gegenereerd is)
- Spelerstatus per ronde (na resultaten invoeren)

---

## Ronde workflow

Elke ronde doorloopt vier statussen in vaste volgorde:

```
GEPLAND → REGISTRATIE GESLOTEN → INGEDEELD → AFGEROND
```

### Stap 1: Gepland

De ronde is aangemaakt en spelers kunnen zich aanmelden of afmelden via hun dashboard. Spelers met auto-deelname worden op het dashboard al als "Beschikbaar (auto)" getoond.

### Stap 2: Registratie sluiten

Klik op **"Registratie sluiten"** als de aanmeldperiode voorbij is.

Wat er gebeurt:
- Alle actieve spelers met **auto-deelname** die zich niet expliciet hebben afgemeld, worden automatisch als "beschikbaar" geregistreerd
- Het aantal auto-geregistreerde spelers wordt getoond
- Spelers kunnen hun beschikbaarheid niet meer wijzigen
- De status wordt **"Registratie gesloten"**

### Stap 3: Indeling genereren

Klik op **"Indeling genereren"** om de automatische indeling te starten.

Het systeem gebruikt:
- **Swiss** (periode 1): scoregroepen op basis van partijresultaten, vast volgnummer op ELO-rating
- **Keizer** (perioden 2-4): ranglijst op basis van Keizerpunten, sterkste tegen sterkste

Na het genereren wordt het aantal partijen en eventuele byes getoond.

**Belangrijk**: De indeling is nog **niet zichtbaar** voor spelers. Je kunt de indeling eerst [aanpassen](#indeling-aanpassen) indien nodig.

Als het genereren mislukt (bijv. te weinig spelers), wordt een foutmelding getoond.

### Stap 3b: Indeling definitief maken

Klik op **"Indeling definitief maken"** zodra de indeling klopt.

- Er moet een indeling bestaan (eerst genereren of handmatig aanmaken)
- De status wordt **"Ingedeeld"**
- De indeling is nu publiek zichtbaar op de indelingspagina

### Stap 4: Resultaten invoeren en ronde afronden

Zie [Resultaten invoeren](#resultaten-invoeren) voor het invullen van resultaten.

Klik na het invoeren van alle resultaten op **"Ronde afronden"**.

Wat er gebeurt:
- De status wordt **"Afgerond"**
- De standen voor het hele seizoen worden automatisch herberekend
- De uitslag is publiek zichtbaar op de uitslagpagina

---

## Indeling aanpassen

Na het automatisch genereren van de indeling (maar voor het definitief maken) kun je de indeling handmatig aanpassen.

### Bordindeling wijzigen

Klik op **"Indeling aanpassen"** om de editor te openen. Hier kun je:

- **Tegenstanders wijzigen**: Selecteer andere spelers voor wit of zwart per bord
- **Borden toevoegen**: Voeg een extra bord toe met beschikbare spelers
- **Borden verwijderen**: Verwijder een bord uit de indeling

**Let op**: Een speler kan niet tegen zichzelf ingedeeld worden. Het systeem controleert dit.

### Kleuren wisselen

Bij elk bord staat een **wisselknop** om wit en zwart om te draaien. Dit werkt per individueel bord en is direct zichtbaar.

### Bye wijzigen

Bij een oneven aantal spelers wordt automatisch een bye toegewezen. In de editor kun je een andere speler als bye-speler selecteren.

### Opnieuw genereren

Je kunt altijd opnieuw op **"Indeling genereren"** klikken om een volledig nieuwe indeling te laten maken. Alle handmatige aanpassingen gaan dan verloren.

---

## Resultaten invoeren

Ga naar de resultatenpagina van een ingedeelde ronde via **"Resultaten invoeren"**.

### Partijresultaten

Per bord selecteer je het resultaat:

| Resultaat | Betekenis |
|---|---|
| **1-0** | Wit wint |
| **0-1** | Zwart wint |
| **remise** | Gelijkspel |
| **\*** | Niet gespeeld (geen resultaat) |

### Spelerstatus

Per speler stel je de status in:

| Status | Betekenis | Punten |
|---|---|---|
| **Gespeeld** | Normale partij gespeeld | Op basis van resultaat en rangwaarde tegenstander |
| **Afwezig** | Niet aanwezig | 20 punten (1e t/m 5e keer), daarna 0 |
| **Externe partij** | Speelde buiten de clubavond | 40 punten (stand voorlopig tot bevestiging) |
| **Bye** | Vrij (oneven aantal) | 40 punten |

### Externe bevestiging

Als een speler als "extern" is gemarkeerd, verschijnt een **bevestigingsvinkje**. Zet dit aan zodra bekend is dat de speler daadwerkelijk extern heeft gespeeld.

Zie [Externe partijen](#externe-partijen) voor de volledige flow.

### Tussentijds opslaan

Je kunt resultaten tussentijds opslaan zonder de ronde af te ronden. Hierdoor kun je gedurende de avond resultaten invoeren en later aanvullen.

---

## Externe partijen

Spelers die op de clubavond niet intern spelen maar elders een partij spelen, worden als "extern" gemarkeerd.

### Flow

1. **Bij resultaten invoeren**: Zet de spelerstatus op **"Externe partij"**
2. **Punten**: De speler ontvangt direct **40 punten** zodra de ronde wordt afgerond, ongeacht de bevestigingsstatus
3. **Voorlopige stand**: Zolang er onbevestigde externe partijen zijn na het afronden van de ronde, wordt de stand als **"voorlopig"** getoond op de publieke pagina
4. **Bevestigen**: Zodra je weet dat de speler daadwerkelijk extern heeft gespeeld, zet je het bevestigingsvinkje aan. De "voorlopig"-markering verdwijnt pas als alle externe partijen bevestigd zijn
5. **Niet gespeeld**: Als de speler uiteindelijk niet heeft gespeeld, wijzig je de status naar **"Afwezig"** en rond je de ronde opnieuw af. De speler ontvangt dan afwezigheidspunten (20 punten, of 0 bij de 6e keer of meer)

### Aandachtspunten

- Resultaten kunnen tussentijds worden opgeslagen, maar de standen worden pas herberekend bij het **afronden** van de ronde
- Als je na het afronden een externe status moet wijzigen, sla je de wijziging op en rond je de ronde opnieuw af om de standen bij te werken
- De "voorlopig"-markering verdwijnt pas als alle externe partijen bevestigd of naar een andere status gewijzigd zijn

---

## Herberekening en standen

### Wanneer worden standen herberekend?

De standen van het hele seizoen worden **volledig herberekend** bij het afronden van een ronde. Dit betekent dat niet alleen de punten van de laatste ronde worden berekend, maar ook alle eerdere rondes opnieuw worden doorgerekend.

### Wat gebeurt er bij een herberekening?

1. De rangwaarden van alle spelers worden opnieuw bepaald op basis van de actuele stand
2. Alle rondepunten worden herberekend met de nieuwe rangwaarden
3. De cumulatieve stand wordt bijgewerkt
4. Positieveranderingen ten opzichte van de vorige ronde worden berekend

### Gevolgen

- Punten uit eerdere rondes kunnen veranderen (doordat tegenstanders gestegen of gedaald zijn)
- Positieveranderingen in de stand worden bijgewerkt
- Statistieken (gewonnen, remise, verloren, extern, bye, afwezig) worden bijgewerkt

Zie de [documentatie over het Keizer puntensysteem](/documentatie/puntensysteem) voor een uitgebreide uitleg over de herberekening.

---

## ELO-ratings en KNSB-update

### Automatische wekelijkse update

De ELO-ratings van spelers worden **automatisch bijgewerkt** op basis van de officiële KNSB-ratinglijst. Dit gebeurt elke maandag om 06:00 — er is geen handmatige actie nodig.

Wat er bij een update gebeurt:
- Het systeem downloadt de nieuwste KNSB-ratinglijst (maandelijks gepubliceerd door de KNSB)
- Spelers met een KNSB-relatienummer krijgen hun ELO-rating automatisch bijgewerkt
- De wijziging wordt vastgelegd in de ratinghistorie van de speler

Als er geen nieuwe ratinglijst beschikbaar is (de KNSB publiceert één keer per maand), doet de update niets. Je hoeft hier niet op te letten.

### Ratinghistorie bekijken

Op de bewerkpagina van een speler (**Beheer → Spelers → [speler]**) zie je:
- De huidige ELO-rating
- Wanneer de rating voor het laatst is gewijzigd

Op de publieke [Ratingspagina](/ratings) zie je (voor spelers die hun rating publiek hebben gemaakt) een grafiek met de historische ratings terug tot februari 2023.

### Handmatige ratingwijziging

Je kunt de ELO-rating van een speler ook handmatig aanpassen via **Beheer → Spelers → [speler bewerken]**. Een handmatige wijziging wordt apart vastgelegd in de ratinghistorie (aangeduid als "Handmatig").

### KNSB-relatienummer toevoegen

Als je een KNSB-relatienummer invult bij een speler, worden **automatisch** historische KNSB-ratings opgehaald (terug tot februari 2023). Dit gebeurt op de achtergrond kort na het opslaan.

---

## Instellingen

Via **Beheer** > **Instellingen** beheer je de weergave van de footer op alle pagina's.

### Contactgegevens

- **Toon contactregel in footer**: Schakel dit aan of uit om het e-mailadres al dan niet te tonen in de footer.
- **E-mailadres wedstrijdleider**: Het e-mailadres dat als klikbare mailto-link in de footer verschijnt.

### Kredietvermelding

- **Toon kredietvermelding in footer**: Schakel dit aan of uit om de "Mogelijk gemaakt door Interio Shops" regel te tonen.
- **Tekst voor logo**: De tekst die vóór het Interio Shops logo verschijnt (standaard: "Mogelijk gemaakt door").
- **URL**: De link waar het Interio Shops logo naartoe verwijst. Leeg laten om het logo zonder link te tonen.

Klik op **Opslaan** om de wijzigingen door te voeren. De footer wordt direct bijgewerkt op alle pagina's.

### Keizer puntensysteem — instellingen

De parameters van het Keizer puntensysteem (zoals de maximale rangwaarde, vaste punten voor afwezigheid, bye en externe partijen, en de minimale deelname voor de eindstand) zijn instelbaar in het systeem. Ze staan op de volgende standaardwaarden:

| Instelling | Standaard | Betekenis |
|---|---|---|
| Maximale rangwaarde | 60 | Rangwaarde van de #1 speler |
| Punten extern | 40 | Punten voor een externe partij |
| Punten afwezig | 20 | Punten per afwezigheid (max. 5×) |
| Punten bye | 40 | Punten voor een bye |
| Max. afwezigheid | 5 | Aantal keren afwezigheidspunten |
| Startpunten nieuwe speler | 15 | Punten per gemiste ronde bij late instroom |
| Factor winst | 1 | Vermenigvuldigingsfactor bij winst |
| Factor remise | 0,5 | Vermenigvuldigingsfactor bij remise |
| Factor verlies | 0 | Vermenigvuldigingsfactor bij verlies |
| Minimale deelname eindstand | 7 | Min. rondes voor vermelding in eindstand |

Deze waarden zijn momenteel **niet aanpasbaar via het beheerpaneel**. Neem contact op met de ontwikkelaar als een van deze waarden gewijzigd moet worden.

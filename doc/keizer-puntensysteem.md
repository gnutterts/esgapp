# Keizer Puntensysteem

## Inhoudsopgave

1. [Waarom Keizerpunten?](#waarom-keizerpunten)
2. [Het basisprincipe](#het-basisprincipe)
3. [Rangwaarden](#rangwaarden)
4. [Punten per ronde](#punten-per-ronde)
5. [Speciale situaties](#speciale-situaties)
6. [Herberekening: waarom de stand steeds verandert](#herberekening-waarom-de-stand-steeds-verandert)
7. [Samenvatting](#samenvatting)

---

## Waarom Keizerpunten?

In de interne competitie worden **altijd** Keizerpunten gebruikt voor de stand, ongeacht het indelingssysteem. Dus ook in periode 1, waar de Swiss-indeling wordt gebruikt, worden de punten berekend met het Keizersysteem.

Het Keizerpuntensysteem is speciaal ontworpen voor clubcompetities. Het verschilt van een standaard Elo-rating op twee belangrijke manieren:

1. **Je verdient meer punten door te winnen van een sterke tegenstander.** Winst tegen de nummer 1 levert meer op dan winst tegen de nummer 20.
2. **De stand wordt na elke ronde volledig herberekend.** Als jouw tegenstander later in het seizoen stijgt op de ranglijst, gaan ook de punten die je eerder tegen hem hebt verdiend omhoog.

---

## Het basisprincipe

Elke speler in de stand heeft een **rangwaarde**. Hoe hoger je staat, hoe hoger je rangwaarde. Als je een partij wint, ontvang je de rangwaarde van je tegenstander als punten. Bij remise ontvang je de helft.

**Voorbeeld**: Je wint van de nummer 3 op de ranglijst (rangwaarde 58). Je ontvangt 58 punten voor die ronde. Had je remise gespeeld, dan kreeg je 29 punten. Bij verlies: 0 punten.

---

## Rangwaarden

Elke positie op de ranglijst heeft een vaste waarde:

| Positie | Rangwaarde |
|---|---|
| #1 | 60 |
| #2 | 59 |
| #3 | 58 |
| #4 | 57 |
| ... | ... |
| #59 | 2 |
| #60 of lager | 1 |

De nummer 1 is altijd 60 waard, ongeacht hoeveel spelers er zijn. De rangwaarden worden **na elke ronde opnieuw bepaald** op basis van de actuele stand.

### Eerste ronde

In de allereerste ronde is er nog geen stand. Dan worden de rangwaarden bepaald op basis van de **ELO-rating** van elke speler. Spelers met een hogere ELO-rating staan hoger op de beginranglijst en hebben dus een hogere rangwaarde.

---

## Punten per ronde

### Gespeelde partijen

| Resultaat | Berekening | Voorbeeld (tegen #3, rangwaarde 58) |
|---|---|---|
| **Winst** | rangwaarde tegenstander | 58 punten |
| **Remise** | rangwaarde tegenstander x 0,5 | 29 punten |
| **Verlies** | 0 | 0 punten |

Je totaalscore is de som van alle rondepunten over het hele seizoen.

### Vaste punten

| Situatie | Punten | Toelichting |
|---|---|---|
| **Extern gespeeld** | 40 | Je speelde een bevestigde partij buiten de clubavond |
| **Afwezig** (1e t/m 5e keer) | 20 | Je deed niet mee |
| **Afwezig** (6e keer en verder) | 0 | Na 5 keer niet meedoen ontvang je geen punten meer |
| **Bye** (oneven) | 40 | Je had automatisch vrij omdat er een oneven aantal spelers was |

---

## Speciale situaties

### Afwezigheidsdrempel

Je ontvangt maximaal **5 keer** afwezigheidspunten (20 punten per keer). Vanaf de 6e keer dat je niet meedoet, ontvang je **0 punten**. Dit voorkomt dat spelers die structureel niet meedoen een te hoge positie innemen.

### Later instromen

Spelers die later in het seizoen instromen krijgen **startpunten** om het verschil met andere spelers enigszins te compenseren:

> Startpunten = (aantal gemiste rondes) x 15

**Voorbeeld**: Je stapt in bij ronde 5. Je hebt 4 rondes gemist. Je startpunten zijn 4 x 15 = **60 punten**. Vanaf dat moment verdien je punten op de normale manier.

### Minimale deelname

Om in de eindresultaten van het seizoen vermeld te worden, moet je minimaal **7 keer** hebben meegedaan aan de interne competitie. Spelers die minder dan 7 keer meedoen worden niet opgenomen in de eindstand.

---

## Herberekening: waarom de stand steeds verandert

Dit is het meest bijzondere aspect van het Keizersysteem en het onderdeel dat de meeste vragen oproept.

Na elke afgeronde ronde wordt de **complete stand van het hele seizoen opnieuw berekend**. Niet alleen de punten van de laatste ronde, maar ook van alle eerdere rondes. Dit komt doordat de rangwaarden van je tegenstanders veranderen naarmate het seizoen vordert.

### Waarom is dit nodig?

De punten die je ontvangt voor een partij hangen af van de **rangwaarde** van je tegenstander. Die rangwaarde is gekoppeld aan hun positie in de stand. Als de stand verandert, veranderen de rangwaarden, en daarmee ook de punten die je eerder hebt verdiend.

### Voorbeeld

**Ronde 1**: Je wint van Bob. Bob staat op dat moment 3e (rangwaarde 58). Je krijgt 58 punten.

**Ronde 5**: Bob is inmiddels gestegen naar de 1e plek (rangwaarde 60). Nu wordt ronde 1 opnieuw berekend: je winst tegen Bob is nu 60 punten waard in plaats van 58.

Omgekeerd geldt hetzelfde: als je tegenstander daalt op de ranglijst, worden je eerder verdiende punten lager.

### Stap voor stap

De herberekening werkt als volgt:

1. Begin bij ronde 1. Bepaal de rangwaarden (in ronde 1: op basis van ELO-rating)
2. Bereken de punten voor elke speler in die ronde
3. Tel alle punten op en maak een nieuwe ranglijst
4. Ga naar de volgende ronde en herhaal (nu met de bijgewerkte rangwaarden)

Dit proces herhaalt zich voor alle afgeronde rondes. De rangwaarden verschuiven gaandeweg, waardoor de punten per ronde steeds nauwkeuriger de werkelijke sterkteverhoudingen weergeven.

### Concreet rekenvoorbeeld

**Seizoen met 3 spelers, 2 rondes:**

**Startpositie** (op basis van ELO-rating):

| Speler | ELO | Rangwaarde |
|---|---|---|
| Alice | 1800 | 60 |
| Bob | 1600 | 59 |
| Carol | 1400 | 58 |

**Ronde 1**: Bob verslaat Alice, Carol heeft bye.
- Alice: verlies = 0 punten
- Bob: winst tegen Alice (rangwaarde 60) = 60 punten
- Carol: bye = 40 punten

**Stand na ronde 1**:

| Pos | Speler | Punten | Nieuwe rangwaarde |
|---|---|---|---|
| 1 | Bob | 60 | 60 |
| 2 | Carol | 40 | 59 |
| 3 | Alice | 0 | 58 |

**Ronde 2**: Alice verslaat Carol, Bob heeft bye.
- Alice: winst tegen Carol (rangwaarde 59) = 59 punten
- Carol: verlies = 0 punten
- Bob: bye = 40 punten

**Totaalstand na ronde 2**:

| Pos | Speler | Ronde 1 | Ronde 2 | Totaal |
|---|---|---|---|---|
| 1 | Bob | 60 | 40 | **100** |
| 2 | Alice | 0 | 59 | **59** |
| 3 | Carol | 40 | 0 | **40** |

---

## Samenvatting

- Keizerpunten worden **altijd** gebruikt, ook in de Swiss-periode
- Je verdient punten op basis van de **rangwaarde van je tegenstander**
- Hoe sterker je tegenstander, hoe meer punten een winst oplevert
- De stand wordt na elke ronde **volledig herberekend** over het hele seizoen
- Eerdere punten kunnen veranderen doordat tegenstanders stijgen of dalen
- Niet meedoen levert 20 punten op (maximaal 5 keer), daarna 0
- Externe partijen en byes leveren vaste punten op (40 per keer)
- Later instromende spelers krijgen startpunten ter compensatie
- Je moet minimaal **7 keer** meedoen om in de eindresultaten te staan

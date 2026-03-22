# Indelingssystemen

## Inhoudsopgave

1. [Overzicht](#overzicht)
2. [Swiss indeling (Periode 1)](#swiss-indeling-periode-1)
3. [Keizer indeling (Perioden 2-4)](#keizer-indeling-perioden-2-4)
4. [Vergelijking Swiss vs Keizer](#vergelijking-swiss-vs-keizer)
5. [Kleurverdeling](#kleurverdeling)
6. [Herindelingsregel](#herindelingsregel)
7. [Bye (oneven aantal spelers)](#bye-oneven-aantal-spelers)

---

## Overzicht

In de interne competitie worden twee indelingssystemen gebruikt, afhankelijk van de periode:

| Periode | Rondes | Indelingssysteem |
|---|---|---|
| Periode 1 | 1 t/m 6 | Swiss |
| Periode 2 | 7 t/m 12 | Keizer |
| Periode 3 | 13 t/m 18 | Keizer |
| Periode 4 | 19 t/m 24 | Keizer |

**Belangrijk**: Het indelingssysteem bepaalt alleen *wie tegen wie speelt*. De puntentelling is altijd het [Keizerpuntensysteem](/documentatie/puntensysteem), ongeacht het indelingssysteem. Zie die pagina voor uitleg over hoe punten worden berekend.

---

## Swiss indeling (Periode 1)

In periode 1 wordt het **Swiss systeem** gebruikt. Dit is een veelgebruikt systeem in schaaktoernooien dat ervoor zorgt dat spelers met vergelijkbare prestaties tegen elkaar spelen.

### Hoe werkt het?

1. **Volgnummer op ELO-rating**: Elke speler krijgt een volgnummer op basis van ELO-rating (hoogste ELO = nummer 1). Dit volgnummer bepaalt de volgorde binnen scoregroepen.

2. **Scoregroepen**: Spelers worden ingedeeld in groepen op basis van hun **partijscore** (winst = 1 punt, remise = 0,5 punt, verlies = 0 punten, bye = 1 punt). Dit zijn *niet* de Keizerpunten — die worden alleen voor de stand gebruikt. In ronde 1 heeft iedereen 0 punten en vormen alle spelers een enkele groep.

3. **Bovenhelft vs onderhelft**: Binnen elke scoregroep wordt de bovenste helft (de spelers met het laagste volgnummer, dus de hoogste ELO) gekoppeld aan de onderste helft:
   - Nummer 1 speelt tegen nummer N/2+1
   - Nummer 2 speelt tegen nummer N/2+2
   - Enzovoort

4. **Floating**: Als een scoregroep een oneven aantal spelers heeft, "zakt" de laagst gerankte speler naar de volgende groep.

### Voorbeeld

8 spelers na ronde 1. Partijscores: Alice (1), Bob (1), Carol (1), Dave (1), Erika (0), Frank (0), Gert (0), Hans (0).

**Scoregroep 1 punt**: Alice, Bob, Carol, Dave
- Alice vs Carol (bord 1)
- Bob vs Dave (bord 2)

**Scoregroep 0 punten**: Erika, Frank, Gert, Hans
- Erika vs Gert (bord 3)
- Frank vs Hans (bord 4)

Zo spelen winnende spelers tegen andere winnaars, en verliezende spelers tegen andere verliezers. De competitie wordt snel uitdagend voor iedereen.

---

## Keizer indeling (Perioden 2-4)

In perioden 2 t/m 4 wordt het **Keizersysteem** gebruikt. Dit is een eenvoudiger systeem dat de sterkste spelers direct tegen elkaar laat spelen.

### Hoe werkt het?

1. Alle spelers worden gesorteerd op hun Keizerpunten (de meeste punten bovenaan). Bij gelijke punten telt de ELO-rating mee.

2. De indeling gaat dan **van boven naar beneden**:
   - Nummer 1 speelt tegen nummer 2
   - Nummer 3 speelt tegen nummer 4
   - Nummer 5 speelt tegen nummer 6
   - Enzovoort

3. Als twee spelers al eerder tegen elkaar gespeeld hebben in dezelfde periode, wordt de volgende beschikbare tegenstander gezocht.

### Voorbeeld

6 spelers gesorteerd op punten: Alice (120), Bob (100), Carol (95), Dave (80), Erika (60), Frank (40).

Alice en Bob hebben al eerder tegen elkaar gespeeld in deze periode:

- Bord 1: Alice vs **Carol** (Bob overgeslagen vanwege herindelingsregel)
- Bord 2: Bob vs Dave
- Bord 3: Erika vs Frank

De sterkste spelers spelen altijd tegen de sterkste tegenstanders die beschikbaar zijn. Door de herindelingsregel krijg je afwisseling in je tegenstanders binnen een periode.

---

## Vergelijking Swiss vs Keizer

| | Swiss (Periode 1) | Keizer (Perioden 2-4) |
|---|---|---|
| **Principe** | Spelers met gelijke partijscore tegen elkaar | Sterkste tegen sterkste |
| **Groepering** | Scoregroepen op basis van partijscore (W=1, R=0,5, V=0, Bye=1) | Ranglijst op basis van Keizerpunten |
| **Volgorde** | Volgnummer op ELO-rating | Keizerpunten + ELO-tiebreaker (verandert per ronde) |
| **Paringsvolgorde** | Bovenhelft vs onderhelft per groep | Nr. 1 vs nr. 2, nr. 3 vs nr. 4, etc. |
| **Floating** | Ja, bij oneven groepen | Niet van toepassing |
| **Geschikt voor** | Begin van het seizoen (alle spelers beginnen gelijk) | Later in het seizoen (stabielere ranglijst) |

---

## Kleurverdeling

Bij elke partij wordt bepaald wie wit en wie zwart speelt. Dit gaat op basis van de volgende regels, in volgorde van prioriteit:

### Harde regels (worden altijd gehandhaafd)

1. **Maximaal 2 keer dezelfde kleur achter elkaar**: Een speler die twee keer achter elkaar wit heeft gespeeld, krijgt gegarandeerd zwart in de volgende ronde (en vice versa).
2. **Kleurverschil maximaal 2**: Een speler mag nooit meer dan 2 partijen meer wit dan zwart hebben gespeeld (of andersom).

### Voorkeur (bij geen harde regels)

1. **Laagste witpercentage krijgt wit**: De speler die relatief het minst vaak wit heeft gespeeld, krijgt wit.
2. **Afwisseling**: Als het witpercentage gelijk is, krijgt de speler die de vorige keer zwart had nu wit.
3. **Hoogst gerankte speler**: Als alles gelijk is, krijgt de hoger gerankte speler wit.

### Voorbeeld

Alice heeft 3 keer wit en 2 keer zwart gespeeld (60% wit). Bob heeft 2 keer wit en 3 keer zwart gespeeld (40% wit). Bob krijgt wit, want hij heeft het laagste witpercentage.

---

## Herindelingsregel

Binnen dezelfde periode mogen twee spelers **niet twee keer tegen elkaar spelen**. Er zijn twee uitzonderingen:

- **Afwezigheid**: Als een of beide spelers bij de vorige ontmoeting afwezig waren (en er dus niet daadwerkelijk gespeeld is), telt die partij niet als een echte ontmoeting. Ze mogen dan opnieuw tegen elkaar ingedeeld worden.

- **Geen andere mogelijkheid**: In uitzonderlijke situaties — bijvoorbeeld bij een kleine groep spelers die al veel rondes hebben gespeeld — kan het voorkomen dat elke denkbare combinatie een herhaling oplevert. Het systeem probeert in dat geval alle mogelijke indelingen uit (inclusief het herzien van al gemaakte paren) om toch een herhaalvrije indeling te vinden. Alleen als dat echt niet lukt, wordt een herhaling alsnog toegestaan. Dit is een laatste redmiddel dat in de praktijk zelden of nooit voorkomt.

Elke nieuwe periode begint de herindelingsregel opnieuw. Dus twee spelers die in periode 2 tegen elkaar speelden, mogen in periode 3 weer tegen elkaar worden ingedeeld.

---

## Bye (oneven aantal spelers)

Als er een oneven aantal spelers beschikbaar is voor een ronde, moet een speler een **bye** krijgen (vrij). Die speler speelt niet maar ontvangt wel punten.

### Wie krijgt de bye?

1. De **laagst gerankte speler** die dit seizoen **nog geen bye** heeft gehad en ook **geen reglementaire winst** heeft gekregen (een winst doordat de tegenstander afwezig was).
2. Als alle spelers al een bye of reglementaire winst hebben gehad, krijgt de laagst gerankte speler de bye.

De bye-speler ontvangt de vaste byepunten (40 punten) voor die ronde.

# KNSB-ratingverwerking — Specificatie

Dit document beschrijft de vereisten en het ontwerp voor de KNSB-ratingfeature: het geschikt maken van de interne competitie voor officiële KNSB-ratingverwerking.

---

## 1. KNSB-vereisten

Om een partij te laten meetellen voor de KNSB-rating moet aan de volgende voorwaarden worden voldaan:

- **Tijdcontrole**: Minimaal 90 minuten per speler voor de gehele partij, of snelschaak-/blitzcontroles conform KNSB-categorie. De interne ESG-competitie speelt met een bedenktiid van 25 min + 10 sec per zet (rapidschaak), wat valt onder de KNSB-categorie "Rapid" (≥ 15 min per speler).
- **Lidmaatschap**: Beide spelers moeten lid zijn van de KNSB (KNSB-relatienummer verplicht).
- **Arbiter**: Een gecertificeerd arbiter (of toezichthoudend lid) moet aanwezig zijn bij de ronde. De arbitergegevens (naam + KNSB-nummer) moeten worden vastgelegd per ronde.
- **Indieningsformaat**: Resultaten worden aangeleverd in Swiss Manager TXT-formaat (`.txt` exportbestand).
- **Indieningsdeadline**: Uiterlijk 2 kalenderdagen vóór de eerste dag van de maand volgend op de speelmaand. Voorbeeld: partijen gespeeld in maart moeten vóór 30 maart zijn ingediend.
- **Registratie**: Het toernooi moet vooraf zijn geregistreerd via [schaakkalender.nl](https://schaakkalender.nl). Er geldt geen gepubliceerd minimaal aantal spelers.

---

## 2. Arbiter-scenario

De wedstrijdleider van ESG is in de meeste gevallen ook deelnemer aan de interne competitie. De KNSB staat toe dat een arbiter meespeelt, mits de eigen partij van de arbiter **niet** wordt ingediend voor ratingverwerking. Dit betekent:

- De partij waarbij de arbiter zelf betrokken is (als wit of zwart) wordt uitgesloten van de KNSB-export.
- Alle overige partijen in de ronde worden wel ingediend.
- De arbiter "speelt geen KNSB-gerater partij" in de zin van het reglement, en voldoet daarmee aan de arbiervereiste.

In de implementatie: bij het genereren van de export worden pairings waarbij `white_user_id` of `black_user_id` gelijk is aan het KNSB-nummer van de arbiter automatisch weggelaten.

---

## 3. Toestemmingsmodel

### Principe

Deelname aan KNSB-ratingverwerking is opt-in per speler per ronde. Een speler geeft toestemming uiterlijk **24 uur vóór de ronddatum**. Na het verstrijken van deze deadline wordt de keuze vergrendeld en kan niet meer worden gewijzigd.

### Opslag

Toestemmingen worden opgeslagen in een nieuwe tabel `knsb_round_consents`:

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | Auto-increment |
| round_id | FK rounds | Ronde |
| user_id | FK users | Speler |
| consented | boolean (default false) | Toestemming gegeven |
| created_at | timestamp | Aangemaakt op |
| updated_at | timestamp | Bijgewerkt op |

Unique constraint: `(round_id, user_id)`.

### Vergrendeling

De toestemming is vergrendeld (niet meer aanpasbaar) zodra `round.date - 24 uur < now()`. De controller valideert dit server-side vóór elke wijziging.

---

## 4. Eligibiliteitscriteria

Een pairing komt in aanmerking voor KNSB-export als aan **alle** onderstaande criteria is voldaan:

1. **KNSB-relatienummer wit**: `white_user.knsb_relatienummer` is ingevuld.
2. **KNSB-relatienummer zwart**: `black_user.knsb_relatienummer` is ingevuld.
3. **Toestemming wit**: `knsb_round_consents` bevat een record met `round_id`, `user_id = white_user_id` en `consented = true`.
4. **Toestemming zwart**: `knsb_round_consents` bevat een record met `round_id`, `user_id = black_user_id` en `consented = true`.
5. **Uitslag bekend**: `pairing.result` is niet null (1-0, 0-1 of remise).
6. **Tijdcontrole ingevuld**: `round.time_control` is ingevuld (bijv. `"25+10"`).
7. **Arbitergegevens ingevuld**: `round.arbiter_name` en `round.arbiter_knsb_id` zijn beide ingevuld.
8. **KNSB-toernooicode ingevuld**: `season.knsb_event_code` is ingevuld.

Bye-pairings en externe partijen worden altijd uitgesloten van de export.

---

## 5. Datamodelwijzigingen

### `rounds` — 4 nieuwe kolommen

| Kolom | Type | Beschrijving |
|---|---|---|
| time_control | string(20), nullable | Tijdcontrole (bijv. `"25+10"` voor 25 min + 10 sec increment) |
| arbiter_name | string(100), nullable | Naam van de arbiter |
| arbiter_knsb_id | string(20), nullable | KNSB-relatienummer van de arbiter |
| knsb_export_generated_at | datetime, nullable | Tijdstip waarop de export voor het laatst is gegenereerd |

### `seasons` — 1 nieuwe kolom

| Kolom | Type | Beschrijving |
|---|---|---|
| knsb_event_code | string(50), nullable | KNSB-toernooicode (via schaakkalender.nl) |

### Nieuwe tabel: `knsb_round_consents`

Zie sectie 3 (Toestemmingsmodel) voor het volledige schema.

---

## 6. Nieuwe componenten

### Model: `KnsbRoundConsent`

`app/Models/KnsbRoundConsent.php`

- Relaties: `round()` (belongsTo Round), `user()` (belongsTo User)
- Scope: `scopeForRound($query, Round $round)`, `scopeConsented($query)`
- Methode: `isLocked(): bool` — geeft `true` als `round.date - 24u < now()`

### Service: `KnsbRapportService`

`app/Services/KnsbRapportService.php`

Verantwoordelijk voor het samenstellen en exporteren van KNSB-ratingrapporten.

**Publieke methoden:**
- `getEligiblePairings(Round $round): Collection` — Haalt alle in aanmerking komende pairings op (zie eligibiliteitscriteria sectie 4).
- `generateTxt(Round $round): string` — Genereert een Swiss Manager-compatibel TXT-exportbestand als string.
- `getExportFilename(Round $round): string` — Geeft de voorgestelde bestandsnaam terug (bijv. `esg-ronde-15-knsb.txt`).

Het Swiss Manager TXT-formaat volgt de specificatie van de KNSB: één regel per partij, vaste kolombreedte, spelernummers gebaseerd op KNSB-relatienummer.

### Controller: `KnsbToestemmingController`

`app/Http/Controllers/KnsbToestemmingController.php`

Middleware: `auth`

| Route | Methode | Beschrijving |
|---|---|---|
| `POST /knsb-toestemming/{ronde}` | `toggle` | Schakelt toestemming voor speler in/uit (valideert deadline) |

### Controller: `KnsbExportController`

`app/Http/Controllers/Beheer/KnsbExportController.php`

Middleware: `auth`, `wedstrijdleider`

| Route | Methode | Beschrijving |
|---|---|---|
| `GET /beheer/rondes/{ronde}/knsb-export` | `show` | Preview: toont eligibele pairings en exportstatus |
| `GET /beheer/rondes/{ronde}/knsb-export/download` | `download` | Genereert en downloadt het TXT-exportbestand |

---

## 7. Beheerstroom

De wedstrijdleider doorloopt de volgende stappen om KNSB-ratingverwerking in te stellen:

1. **Seizoen registreren**: Registreer het toernooi op schaakkalender.nl en noteer de KNSB-toernooicode.
2. **Toernooicode invoeren**: Voer de code in bij `Beheer → Seizoenen → [seizoen bewerken]` (`knsb_event_code`).
3. **Per ronde instellen**: Vul vóór elke ronde in bij `Beheer → Rondes → [ronde bewerken]`:
   - Tijdcontrole (`time_control`, bijv. `25+10`)
   - Arbiternaam (`arbiter_name`)
   - Arbiter KNSB-nummer (`arbiter_knsb_id`)
4. **Na afloop van een ronde**: Ga naar `Beheer → Rondes → [ronde] → KNSB-export`. Controleer welke pairings in aanmerking komen en download het TXT-bestand.
5. **Indienen bij KNSB**: Dien het TXT-bestand in via de KNSB-portal vóór de indieningsdeadline (zie sectie 1).

---

## 8. Dashboard voor spelers

Op het spelersdashboard verschijnt bij aankomende rondes een toggle "Mijn partij mag KNSB meetellen" indien:
- De ronde een `time_control` heeft ingevuld (d.w.z. KNSB-verwerking is actief voor die ronde), en
- De speler een `knsb_relatienummer` heeft.

De toggle toont de huidige toestemmingsstatus en is klikbaar zolang de deadline (T-24u) niet is verstreken. Na de deadline wordt de toggle weergegeven als vergrendeld (niet aanpasbaar), inclusief een korte uitleg.

**UI-logica:**
- Geen `knsb_relatienummer` → toggle niet zichtbaar (speler is sowieso niet eligible)
- Geen `time_control` op ronde → toggle niet zichtbaar (ronde doet niet mee aan KNSB)
- Deadline verstreken → toggle zichtbaar maar uitgeschakeld, label: "Vergrendeld"
- Anders → toggle actief, POST naar `KnsbToestemmingController@toggle`

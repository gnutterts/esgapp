# Architectuur

## Technologiestack

| Laag | Technologie |
|---|---|
| Backend | Laravel 12, PHP 8.4 |
| Database | MySQL 8 (database: `esgapp`) |
| Frontend | Blade templates + Tailwind CSS v4 (via Vite 7) |
| JavaScript | Vanilla JS (geen framework): formulier double-submit preventie |
| E-mail | sendmail (Plesk mailserver) |
| Authenticatie | Passwordless login via e-mailcode (eigen implementatie) |
| Sessie/Cache/Queue | Database-driver |
| Testing | PHPUnit 11, SQLite in-memory |

## Database Schema

### Entity-Relationship Diagram

```
Season ──1:N──> Period ──1:N──> Round
                                  ├──1:N──> Registration ──N:1──> User
                                  ├──1:N──> Pairing ──N:1──> User (wit/zwart/bye)
                                  ├──1:N──> Standing ──N:1──> User
                                  └──1:N──> RoundPlayerStatus ──N:1──> User

User ──1:N──> EloRating
User ──1:N──> MagicLink
User.joined_at_round_id ──N:1──> Round
```

### Tabellen

#### `users`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | Auto-increment |
| name | string | Volledige naam |
| email | string, unique | E-mailadres (login identifier) |
| role | enum: speler, wedstrijdleider | Gebruikersrol |
| is_active | boolean (default true) | Actieve speler |
| auto_participate | boolean (default false) | Automatisch aanmelden voor nieuwe rondes |
| elo_rating | int, nullable | KNSB ELO-rating (voor beginrangschikking, hoger = sterker) |
| knsb_relatienummer | string(20), nullable, unique | KNSB relatienummer |
| show_knsb_rating | boolean (default true) | KNSB-rating tonen op publieke pagina |
| joined_at_round_id | FK rounds, nullable | Ronde waarin speler mid-seizoen is toegetreden |

**Let op**: Geen `password` of `email_verified_at` kolom. Dit is een passwordless applicatie.

#### `seasons`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| name | string | Seizoensnaam (bijv. "2025-2026") |
| start_date | date | Startdatum |
| end_date | date | Einddatum |
| is_current | boolean | Huidige actieve seizoen |

#### `periods`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| season_id | FK seasons | Seizoen |
| number | tinyint (1-4) | Periodenummer |
| pairing_system | enum: swiss, keizer | Indelingssysteem voor deze periode |

Unique constraint: `(season_id, number)`. Periode 1 = Swiss, Perioden 2-4 = Keizer (automatisch aangemaakt bij seizoen).

#### `rounds`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| period_id | FK periods | Periode |
| round_number | tinyint (1-6) | Rondenummer binnen periode |
| season_round_number | tinyint (1-24) | Rondenummer binnen seizoen |
| date | date | Speeldatum |
| status | enum | scheduled, registration_closed, paired, completed |
| registration_deadline | datetime, nullable | Deadline voor aan-/afmelden (null = handmatig sluiten) |

Unique constraint: `(period_id, round_number)`.

#### `registrations`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| round_id | FK rounds | Ronde |
| user_id | FK users | Speler |
| status | enum: available, unavailable | Beschikbaarheid |

Unique constraint: `(round_id, user_id)`.

#### `pairings`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| round_id | FK rounds | Ronde |
| board_number | int | Bordnummer |
| white_user_id | FK users, nullable, nullOnDelete | Witspeler |
| black_user_id | FK users, nullable, nullOnDelete | Zwartspeler |
| result | enum, nullable | 1-0, 0-1, remise |
| is_bye | boolean (default false) | Bye-partij |
| bye_user_id | FK users, nullable, nullOnDelete | Speler met bye |

#### `standings`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| round_id | FK rounds | Ronde |
| user_id | FK users | Speler |
| position | int | Positie in de stand |
| position_change | int (default 0) | Verandering t.o.v. vorige ronde |
| points | decimal(8,2) | Totaal punten |
| games_played | int | Aantal gespeelde partijen |
| color_balance | int | Kleurbalans (+1 wit, -1 zwart) |
| wins | int | Aantal gewonnen |
| draws | int | Aantal remise |
| losses | int | Aantal verloren |
| external_count | int | Aantal externe partijen |
| bye_count | int | Aantal byes |
| absence_count | int | Aantal keer afwezig |

Unique constraint: `(round_id, user_id)`.

#### `round_player_statuses`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| round_id | FK rounds | Ronde |
| user_id | FK users | Speler |
| status | enum | played, absent, absent_6plus, external, bye |
| is_external_confirmed | boolean (default false) | Externe partij bevestigd |

Unique constraint: `(round_id, user_id)`.

#### `magic_links`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| user_id | FK users | Gebruiker |
| token | string(64), unique | Login code (SHA-256 hash van de 6-cijferige code) |
| expires_at | datetime | Verloopdatum (15 min na aanmaak) |
| used_at | datetime, nullable | Gebruikt op |
| created_at | timestamp | Aangemaakt op |

Geen `updated_at` kolom. Codes worden gehashed opgeslagen; de ongehashte 6-cijferige code wordt per e-mail verstuurd. Bij een nieuwe aanvraag worden bestaande ongebruikte codes voor dezelfde gebruiker ongeldig gemaakt.

#### `settings`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| key | string, unique | Instelling-sleutel |
| value | string NOT NULL | Waarde |

Bekende sleutels:

| Sleutel | Standaard | Beschrijving |
|---|---|---|
| `footer_contact_email` | `wedstrijdleider@esgapp.nl` | E-mailadres in footer |
| `footer_toon_contact` | `1` | Contactregel tonen in footer (0/1) |
| `footer_toon_kredit` | `1` | Kredietvermelding tonen in footer (0/1) |
| `footer_kredit_prefix` | `Mogelijk gemaakt door` | Tekst voor het Interio Shops logo |
| `footer_kredit_url` | `https://interioshops.nl` | URL van het Interio Shops logo |

#### `elo_ratings`

| Kolom | Type | Beschrijving |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, cascadeOnDelete | Speler |
| rating | int | ELO-rating |
| source | string, nullable | Bron: 'knsb' of 'manual' |
| measured_at | date | Meetdatum |
| created_at | timestamp | Aangemaakt op |

Geen `updated_at` kolom. Index op `(user_id, measured_at)`.

#### `sessions`

Standaard Laravel sessietabel voor database-sessiedriver.

#### `jobs` / `failed_jobs`

Standaard Laravel wachtrij-tabellen voor de `database` queue driver. `jobs` bevat openstaande taken; `failed_jobs` bevat mislukte taken met stacktrace.

#### `cache`

Standaard Laravel cachetabel voor de `database` cache driver.

---

## Models

### Relaties

| Model | Relatie | Type |
|---|---|---|
| User | registrations | hasMany |
| User | pairingsAsWhite | hasMany (FK: white_user_id) |
| User | pairingsAsBlack | hasMany (FK: black_user_id) |
| User | standings | hasMany |
| User | roundPlayerStatuses | hasMany |
| User | magicLinks | hasMany |
| User | eloRatings | hasMany |
| User | joinedAtRound | belongsTo Round |
| EloRating | user | belongsTo |
| Season | periods | hasMany |
| Period | season | belongsTo |
| Period | rounds | hasMany |
| Round | period | belongsTo |
| Round | registrations, pairings, standings, roundPlayerStatuses | hasMany |
| Registration | round, user | belongsTo |
| Pairing | round, whitePlayer, blackPlayer, byePlayer | belongsTo |
| Standing | round, user | belongsTo |
| RoundPlayerStatus | round, user | belongsTo |
| MagicLink | user | belongsTo |

### Speciale methods

- **Season**: `scopeCurrent()` — filtert op `is_current = true`
- **MagicLink**: `isValid()` — niet verlopen en niet gebruikt; `scopeValid()` — query scope
- **Setting**: `Setting::get($key, $default)` — waarde ophalen; `Setting::set($key, $value)` — upsert

---

## Services

### KeizerPointsService (`app/Services/KeizerPointsService.php`)

Berekent alle punten en standen voor een seizoen. Zie [keizer-puntensysteem.md](../keizer-puntensysteem.md) voor details. Laadt alle settings in één query bij constructie. Alle schrijfoperaties draaien in een database-transactie.

**Publieke methods:**
- `recalculateStandings(Season $season)` — Volledige herberekening van het seizoen (transactional)
- `calculateRoundPoints(Round $round, array $rankValues)` — Punten voor een ronde
- `getRankValues(array $cumulativePoints)` — Rangwaarden berekenen
- `getInitialRankValues(Season $season)` — Beginrangwaarden
- `updateStandings(Round $round, ...)` — Standrecords bijwerken

### PairingService (`app/Services/PairingService.php`)

Orchestrator die de juiste indelingsengine selecteert op basis van het periodesysteem. Verwijderen van bestaande pairings en genereren van nieuwe draaien in een database-transactie.

### AbstractPairingEngine (`app/Services/AbstractPairingEngine.php`)

Abstracte basisklasse met gedeelde logica voor beide indelingsengines:
- `getAvailablePlayers()` — Beschikbare spelers ophalen
- `getPlayerPoints()` — Keizer-punten uit vorige ronde (alleen gebruikt door Keizer-engine)
- `getGameScores()` — Partijscores (W=1, R=0.5, V=0) voor Swiss scoregroepen
- `getPeriodPairings()` — Eerdere pairings in periode (voor herindelingsregel)
- `cannotPair()` — Herindelingsregel controleren
- `getColorHistory()` / `assignColors()` — Percentage-gebaseerde kleurverdeling met harde constraints (max ±2 balans, geen 3 achtereen)
- `hasColorConflict()` — Controleer of een paring een onoplosbaar kleurconflict heeft
- `getByeHistory()` / `selectByePlayer()` — Bye-selectie (inclusief reglementaire winsten als bye-historie)

### SwissPairingEngine (`app/Services/SwissPairingEngine.php`)

Swiss indelingsalgoritme voor periode 1. Extends `AbstractPairingEngine`. Sorteert spelers op partijscore (game scores) in scoregroepen, met vast volgnummer op ELO-rating (FIDE C.04.3). Gebruikt **geen** Keizerpunten. Zie [indelingsalgoritmen.md](../indelingsalgoritmen.md).

### KeizerPairingEngine (`app/Services/KeizerPairingEngine.php`)

Keizer indelingsalgoritme voor perioden 2-4. Extends `AbstractPairingEngine`. Sorteert op Keizerpunten met ELO-tiebreaker bij gelijke punten. Zie [indelingsalgoritmen.md](../indelingsalgoritmen.md).

---

## Controllers

| Controller | Prefix | Middleware | Beschrijving |
|---|---|---|---|
| HomeController | `/` | geen | Publieke homepage |
| PublicController | `/stand`, `/indeling`, `/uitslag` | geen | Publieke pagina's |
| MagicLinkController | `/login`, `/login/verifieer`, `/uitloggen` | throttle:5,1 (POST) | Authenticatie |
| DocumentatieController | `/documentatie` | geen | Publieke documentatiepagina's |
| DashboardController | `/dashboard` | auth | Speler dashboard |
| RatingController | `/ratings` | geen (publiek) / auth (toggle) | Publieke ratingpagina (KNSB) + toggle zichtbaarheid |
| RegistrationController | `/registratie`, `/auto-deelname` | auth | Registratie toggle |
| SeizoenController | `/beheer/seizoenen` | auth, wedstrijdleider | Seizoenbeheer (inclusief verwijderen) |
| RondeController | `/beheer/rondes` | auth, wedstrijdleider | Rondebeheer |
| SpelerController | `/beheer/spelers` | auth, wedstrijdleider | Spelerbeheer |
| InstellingenController | `/beheer/instellingen` | auth, wedstrijdleider | Footer-instellingen beheer |

---

## Middleware

### SecurityHeaders

Globaal geregistreerd in `bootstrap/app.php`. Voegt beveiligingsheaders toe aan elke response:
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy: default-src 'self'; ...` (script-src, style-src, font-src, img-src)
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`

### EnsureWedstrijdleider

Geregistreerd als alias `wedstrijdleider` in `bootstrap/app.php`. Controleert of de ingelogde gebruiker `role === 'wedstrijdleider'` heeft. Retourneert 403 bij onvoldoende rechten.

## Frontend

### Tailwind CSS v4 thema

Geen `tailwind.config.js`. Themakleuren zijn gedefinieerd via `@theme` in `resources/css/app.css`:

```css
@theme {
  --color-navy: #1e3a5f;
  --color-navy-light: #2a4f7f;
  --color-navy-dark: #162d4a;
}
```

Dit genereert Tailwind utility classes zoals `bg-navy`, `text-navy-light`, `border-navy-dark`, etc. Alle views gebruiken deze themakleuren (geen hardcoded hex-waarden).

### Blade partials

Gedeelde view-componenten in `resources/views/partials/`:

| Partial | Gebruikt in | Beschrijving |
|---|---|---|
| `round-navigation.blade.php` | indeling, uitslag | Vorige/volgende ronde navigatie |
| `standings-table.blade.php` | stand, uitslag | Standenlijst met legenda, mobiel-responsive |
| `round-status-badge.blade.php` | beheer rondes, seizoenen, dashboard | Rondestatus badge met kleurcodering |
| `dashboard/period-info.blade.php` | dashboard | Huidige periode + indelingssysteem info |
| `dashboard/recent-rounds.blade.php` | dashboard | Laatste afgeronde rondes met resultaat |
| `dashboard/standing-card.blade.php` | dashboard | Huidige positie en punten van de speler |
| `dashboard/toggles.blade.php` | dashboard | Auto-deelname en rating-zichtbaarheid toggles |
| `dashboard/upcoming-rounds.blade.php` | dashboard | Aankomende rondes met registratiestatus |

### Blade components

Herbruikbare anonieme Blade components in `resources/views/components/`:

| Component | Props | Beschrijving |
|---|---|---|
| `x-button` | `variant` (primary/secondary/danger/warning/success), `size` (sm/md/lg), `href` | Knop of link-als-knop |
| `x-card` | `title`, `$actions` (named slot), `padded` (bool), `flush` (bool) | Kaart met optionele navy titelheader |
| `x-badge` | `variant` (gray/blue/green/yellow/red/purple/amber) | Inline kleurcodering badge |
| `x-input` | `label`, `name`, `type`, `error`, `helpText` | Tekstinvoerveld met label en foutmelding |
| `x-select` | `label`, `name`, `error`, `helpText` | Selectieveld met label en foutmelding |
| `x-empty-state` | `icon` (SVG path), `message` | Lege-staat melding met icoon |
| `x-back-link` | `href` | Terug-link met pijl |
| `x-toggle` | `name`, `value` (bool), `label`, `action` (route), `ariaLabel` | Accessible POST-form toggle (`role="switch"`) |

### JavaScript (`resources/js/app.js`)

Minimale vanilla JS:
- **Double-submit preventie**: luistert naar `submit` events op niet-GET formulieren, schakelt submitknoppen uit met visuele feedback (`opacity-50 cursor-not-allowed`), herstelt na 5 seconden als vangnet

### Layout

- `layouts/app.blade.php`: hoofdlayout met navy navbar, flash messages (`success`, `error`, `info`), en `@stack('scripts')` voor pagina-specifiek JavaScript
- `layouts/beheer.blade.php`: beheerlayout met zijbalk, breidt `app.blade.php` uit

## Artisan Commands

### `magic-links:clean`

Verwijdert verlopen en gebruikte inlogcodes uit de database. Draait dagelijks via de scheduler (gedefinieerd in `routes/console.php`).

### `elo:update`

Controleert of er een nieuwe KNSB ratinglijst beschikbaar is (maandelijks). Downloadt de lijst en werkt ELO-ratings bij voor alle spelers met een KNSB-nummer. Draait wekelijks op maandag om 06:00 via de scheduler.

---

## KnsbRatingService

`app/Services/KnsbRatingService.php`

Service voor het ophalen en verwerken van KNSB-ratinglijsten.

**Download en opslag:**
- Downloadt maandelijkse ratinglijsten van schaakbond.nl (ZIP-archief met daarin `KLASSIEK.csv`)
- Bestanden worden opgeslagen in `storage/app/knsb/` onder de naam `KLASSIEK-YYYY-MM.csv`
- Download-URL is gebaseerd op jaar en maand; de service bepaalt zelf welke maandlijst actueel is

**Publieke methoden:**
- `downloadLatest(): ?string` — Downloadt de meest recente ratinglijst en slaat deze op; retourneert het bestandspad of `null` bij mislukking
- `parseRatings(string $path): array` — Parseert een opgeslagen CSV-bestand naar een associatieve array (KNSB-relatienummer → rating)
- `updateAllPlayers(): int` — Werkt `elo_rating` bij voor alle spelers met een KNSB-nummer op basis van de nieuwste beschikbare lijst; retourneert het aantal bijgewerkte spelers

**Foutafhandeling:**
- Als de download mislukt (netwerk, HTTP-fout, ongeldige ZIP), blijven bestaande ratings ongewijzigd
- Fouten worden gelogd via Laravel's logging; de command `elo:update` rapporteert succes/mislukking in de console-output

**Relatie met `elo_ratings` tabel:**
- Elke bijwerking schrijft een nieuw record in `elo_ratings` met `source = 'knsb'` en `measured_at` op de peildatum van de ratinglijst
- De kolom `users.elo_rating` wordt bijgewerkt naar de meest recente waarde (gebruikt voor indelingsvolgorde)

**`ImportKnsbRatings` job** (`app/Jobs/ImportKnsbRatings.php`):
- Getriggerd wanneer een `knsb_relatienummer` wordt toegevoegd aan een speler (via `SpelerController`)
- Importeert historische ratings van februari 2023 tot heden voor die speler
- Queue-instellingen: `retries = 2`, `timeout = 600` seconden
- Draait via de `database` queue-driver; worker wordt elke minuut gestart via Plesk cron



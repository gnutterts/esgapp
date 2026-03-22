# Testing

## Overzicht

De applicatie heeft **72 tests** met **241 assertions**. Tests draaien op SQLite in-memory voor snelheid.

```bash
# Alle tests uitvoeren
php artisan test

# Specifieke test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Specifiek testbestand
php artisan test tests/Unit/KeizerPointsServiceTest.php

# Met uitgebreide output
php artisan test --verbose
```

## Testconfiguratie

In `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="MAIL_MAILER" value="array"/>
```

Alle tests gebruiken de `RefreshDatabase` trait voor een schone database per test.

> **Let op — config cache**: Als de productie config cache actief is (`bootstrap/cache/config.php`), negeren Laravel's `<env>` overrides in `phpunit.xml` de gecachte waarden niet. Tests draaien dan per ongeluk tegen de productiedatabase. Voer altijd `php artisan config:clear` uit vóór het draaien van tests, en herstel daarna met `php artisan config:cache`.

## Unit Tests

### KeizerPointsServiceTest (11 tests)

Test de Keizer puntenberekening in `App\Services\KeizerPointsService`.

| Test | Beschrijving |
|---|---|
| `test_initial_rank_values_based_on_elo_rating` | Rangwaarden gebaseerd op elo_rating (hogere ELO = hogere rangwaarde) |
| `test_higher_elo_gets_higher_rank_value` | Hogere ELO → hogere rangwaarde, spelers zonder ELO krijgen standaard 1200 |
| `test_win_gives_opponent_rank_value_points` | Winst geeft rangwaarde tegenstander als punten |
| `test_draw_gives_half_opponent_rank_value` | Remise geeft 0.5 x rangwaarde tegenstander |
| `test_loss_gives_zero_points` | Verlies geeft 0 punten |
| `test_external_gives_40_points` | Externe partij geeft 40 punten |
| `test_absence_gives_20_points` | Afwezigheid geeft 20 punten |
| `test_absence_over_5_gives_zero_points` | 6e afwezigheid geeft 0 punten |
| `test_bye_gives_40_points` | Bye geeft 40 punten |
| `test_mid_season_joiner_gets_starting_points` | Nieuwe speler krijgt (ronde-1) x 15 startpunten |
| `test_full_recalculation_across_multiple_rounds` | Volledige herberekening over meerdere rondes |

### SwissPairingEngineTest (9 tests)

Test het Swiss indelingsalgoritme in `App\Services\SwissPairingEngine`.

| Test | Beschrijving |
|---|---|
| `test_pairs_players_in_score_brackets` | Spelers worden gegroepeerd en gepaird in score brackets |
| `test_no_repeat_pairing_in_period` | Geen herindeling binnen dezelfde periode |
| `test_repeat_allowed_if_default_loss` | Herindeling toegestaan als een speler afwezig was |
| `test_odd_player_gets_bye` | Oneven aantal spelers: laagste krijgt bye |
| `test_color_alternation` | Kleuren alterneren tussen rondes |
| `test_round1_sorts_by_elo_as_tiebreaker` | Ronde 1: bij gelijke punten sorteren op ELO |
| `test_brackets_use_game_scores_not_keizer_points` | Scoregroepen gebruiken partijscores (W=1, R=0.5, V=0), niet Keizerpunten |
| `test_color_conflict_avoidance_in_swiss` | Paring wordt overgeslagen bij onoplosbaar kleurconflict (3 keer dezelfde kleur) |
| `test_swiss_bye_excludes_forfeit_winners` | Spelers met reglementaire winst (tegenstander afwezig) worden bij bye-selectie behandeld als bye-ontvangers |

### KeizerPairingEngineTest (10 tests)

Test het Keizer indelingsalgoritme in `App\Services\KeizerPairingEngine`.

| Test | Beschrijving |
|---|---|
| `test_pairs_by_ranking_order` | Paren op rangorde: #1 vs #2, #3 vs #4 |
| `test_skips_repeat_opponents` | Slaat tegenstanders over die al gespeeld zijn in periode |
| `test_odd_player_gets_bye` | Bye-toewijzing bij oneven aantal |
| `test_color_balance_considered` | Kleurtoewijzing op basis van balans |
| `test_elo_tiebreaker_in_ranking` | Bij gelijke Keizerpunten wordt ELO-rating als tiebreaker gebruikt |
| `test_safe_force_pairing_gives_bye_not_double_bye` | Force-pairing fallback geeft bye aan restspeler in plaats van dubbele bye |
| `test_bye_prefers_player_without_prior_bye` | Bye-selectie geeft voorkeur aan spelers die nog geen bye hebben gehad |
| `test_percentage_based_color_allocation` | Kleurverdeling op basis van witpercentage (laagste witpercentage krijgt wit) |
| `test_no_three_consecutive_same_color` | Harde regel: nooit 3 keer dezelfde kleur achter elkaar |
| `test_forfeit_win_counts_as_bye_in_history` | Reglementaire winst (tegenstander afwezig) telt mee als bye in bye-historie |

## Feature Tests

### MagicLinkTest (6 tests)

Test de passwordless authenticatie flow via e-mailcodes.

| Test | Beschrijving |
|---|---|
| `test_login_page_loads` | Login pagina retourneert HTTP 200 |
| `test_magic_link_sent_for_valid_email` | Geldige e-mail: MagicLink record aangemaakt (gehashte code), redirect naar verificatiepagina |
| `test_magic_link_redirect_for_unknown_email` | Onbekend e-mail: redirect naar verificatiepagina (geen foutmelding, voorkomt user enumeration) |
| `test_valid_token_logs_in_user` | Geldige code: gebruiker ingelogd, sessie geregenereerd, redirect naar dashboard |
| `test_expired_token_rejected` | Verlopen code: redirect met foutmelding |
| `test_used_token_rejected` | Gebruikte code: redirect met foutmelding |

### RegistrationTest (16 tests)

Test de speelregistratie flow en het auto-deelname systeem.

| Test | Beschrijving |
|---|---|
| `test_can_toggle_registration` | Beschikbaarheid wisselen: available ↔ unavailable |
| `test_cannot_register_after_deadline` | Registratie na deadline wordt geweigerd |
| `test_cannot_register_when_round_is_not_scheduled` | Registratie bij niet-geplande ronde wordt geweigerd |
| `test_can_toggle_auto_participate` | Auto-deelname in/uitschakelen |
| `test_auto_participate_user_first_toggle_creates_unavailable` | Eerste toggle voor auto-deelname speler maakt unavailable registratie |
| `test_auto_participate_user_toggle_from_unavailable_deletes_registration` | Toggle vanuit unavailable verwijdert registratie (terug naar auto) |
| `test_auto_participate_user_toggle_from_available_sets_unavailable` | Toggle vanuit available zet naar unavailable (expliciete opt-out) |
| `test_auto_participate_full_toggle_cycle` | Volledige cyclus: auto → unavailable → auto → available → unavailable |
| `test_disabling_auto_participate_makes_user_unavailable_for_open_rounds` | Uitschakelen auto-deelname maakt speler unavailable voor open rondes |
| `test_disabling_auto_participate_preserves_explicit_opt_out` | Uitschakelen auto-deelname behoudt expliciete opt-out registraties |
| `test_close_registration_materializes_auto_participate_users` | Sluiten registratie maakt available registraties aan voor auto-deelname spelers |
| `test_close_registration_respects_explicit_opt_out` | Sluiten registratie respecteert expliciete opt-out van auto-deelname spelers |
| `test_close_registration_skips_inactive_auto_participate_users` | Sluiten registratie slaat inactieve auto-deelname spelers over |
| `test_creating_round_does_not_auto_create_registrations` | Aanmaken ronde maakt geen automatische registraties aan |
| `test_can_create_round_without_registration_deadline` | Ronde aanmaken zonder deadline is toegestaan (deadline is optioneel) |
| `test_can_register_when_no_deadline_set` | Registratie mogelijk wanneer ronde geen deadline heeft |

### RatingPageTest (11 tests)

Test de publieke ratingpagina, de zichtbaarheidstoggle en de KNSB import job dispatch.

| Test | Beschrijving |
|---|---|
| `test_ratings_page_shows_only_public_players` | Alleen spelers met show_knsb_rating=true worden getoond |
| `test_ratings_show_page_works_for_public_player` | Individuele ratingpagina toont ELO en historie |
| `test_ratings_show_page_404_for_hidden_player` | 404 voor spelers met zichtbaarheid uitgeschakeld |
| `test_toggle_show_knsb_rating` | Toggle wisselt show_knsb_rating en redirect naar dashboard |
| `test_toggle_show_rating_requires_auth` | Toggle zonder login redirect naar login |
| `test_toggle_show_rating_rejects_invalid_type` | Ongeldig type in toggle URL geeft 404 |
| `test_store_with_knsb_dispatches_import_job` | Speler aanmaken met KNSB-nummer dispatcht ImportKnsbRatings job |
| `test_store_without_knsb_does_not_dispatch_job` | Speler aanmaken zonder KNSB-nummer dispatcht geen job |
| `test_update_with_changed_knsb_dispatches_import_job` | Wijziging KNSB-nummer dispatcht ImportKnsbRatings job |
| `test_update_without_knsb_change_does_not_dispatch_job` | Ongewijzigd KNSB-nummer dispatcht geen job |
| `test_elo_history_shown_on_player_page` | Ratinghistorie (KNSB/handmatig) wordt getoond |

### ResultsTest (3 tests)

Test het invoeren van resultaten door de wedstrijdleider.

| Test | Beschrijving |
|---|---|
| `test_wedstrijdleider_can_enter_results` | Wedstrijdleider kan resultaten opslaan |
| `test_completing_round_recalculates_standings` | Afronden herberekent standen (Standing records bestaan) |
| `test_non_admin_cannot_access_results` | Niet-wedstrijdleider krijgt HTTP 403 |

## Test data opzetten

Tests maken hun eigen testdata aan. Veelgebruikt patroon:

```php
// Seizoen met perioden en ronde
$season = Season::create([...]);
$period = Period::create(['season_id' => $season->id, 'number' => 1, 'pairing_system' => 'swiss']);
$round = Round::create(['period_id' => $period->id, ...]);

// Spelers — Let op: 'role' is niet mass assignable, moet expliciet gezet worden
$player1 = new User(['name' => 'Speler 1', 'email' => '...', 'is_active' => true, 'auto_participate' => false]);
$player1->role = 'speler';
$player1->save();

// Registraties
Registration::create(['round_id' => $round->id, 'user_id' => $player1->id, 'status' => 'available']);

// Pairings en resultaten
Pairing::create(['round_id' => $round->id, 'white_user_id' => $player1->id, ...]);
RoundPlayerStatus::create(['round_id' => $round->id, 'user_id' => $player1->id, 'status' => 'played']);

// Login codes — codes worden gehashed opgeslagen
$code = '123456';
MagicLink::create(['user_id' => $user->id, 'token' => hash('sha256', $code), 'expires_at' => now()->addMinutes(15)]);
```

De Keizer settings worden in `setUp()` geseed voor tests die puntenberekening vereisen.

## Nieuwe tests toevoegen

```bash
# Maak een nieuw testbestand aan
php artisan make:test NieuweFeatureTest          # Feature test
php artisan make:test NieuweUnitTest --unit       # Unit test
```

Zorg dat elke test:
1. De `RefreshDatabase` trait gebruikt
2. Benodigde settings seed in `setUp()` (als Keizer-punten nodig zijn)
3. Alle benodigde testdata zelf aanmaakt
4. CSRF middleware niet nodig is (uitgeschakeld in TestCase)

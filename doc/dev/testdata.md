# Testdata — Gesimuleerd Seizoen

Dit document beschrijft de vaste testdataset die via `php artisan seizoen:simuleer --force` wordt aangemaakt voor het "Test seizoen". Het dient als referentie voor ontwikkeling en handmatig testen.

---

## Hoe opnieuw aanmaken

```bash
# Verwijder bestaande rondedata en simuleer opnieuw
php artisan tinker --no-interaction <<'EOF'
$season = \App\Models\Season::current()->first();
$roundIds = \App\Models\Round::whereHas('period', fn($q) => $q->where('season_id', $season->id))->pluck('id');
\App\Models\Standing::whereIn('round_id', $roundIds)->delete();
\App\Models\RoundPlayerStatus::whereIn('round_id', $roundIds)->delete();
\App\Models\Pairing::whereIn('round_id', $roundIds)->delete();
\App\Models\Registration::whereIn('round_id', $roundIds)->delete();
\App\Models\Round::whereIn('id', $roundIds)->delete();
\App\Models\User::whereNotNull('joined_at_round_id')->update(['joined_at_round_id' => null]);
EOF

php artisan seizoen:simuleer --force
```

---

## Seizoen

| Veld | Waarde |
|---|---|
| Naam | Test seizoen |
| Startdatum | 15 september 2025 |
| Einddatum | 29 juni 2026 |
| Rondes | 20 voltooid + 3 gepland |
| Perioden | 4 (Swiss × 1, Keizer × 3) |

### Rondeverdeling

| Periode | Rondes | Systeem | Status |
|---|---|---|---|
| 1 | 1 t/m 6 | Swiss | Voltooid |
| 2 | 7 t/m 12 | Keizer | Voltooid |
| 3 | 13 t/m 18 | Keizer | Voltooid |
| 4 | 19 t/m 20 | Keizer | Voltooid |
| 4 | 21 t/m 23 | Keizer | Gepland |

Speeldata: elke 2 weken vanaf 15-09-2025. Geplande rondes: 22-06-2026, 06-07-2026, 20-07-2026.

---

## Spelers

22 actieve spelers: 1 wedstrijdleider (Gert Nutterts, `auto_participate = true`) en 21 spelers.

| # | Naam | ELO | KNSB-nr | Bijzonderheden |
|---|---|---|---|---|
| 1 | Gert Nutterts | 1200 | 9033475 | Wedstrijdleider, auto-deelname aan |
| 2 | Willem Boontje | 1940 | 8419356 | |
| 3 | Jan Willem Brinks | 1986 | 7845123 | |
| 4 | Stef Dubbeldam | 1834 | 8834089 | |
| 5 | Raymond Hof | 1709 | 8605025 | |
| 6 | Evert Hondema | 1945 | 7363312 | |
| 7 | Rienk Hoogeveen | 1952 | 7379768 | |
| 8 | Frank Kieft | — | — | Late inschrijver: start periode 3 (ronde 13) |
| 9 | Jarno Koopman | — | — | Late inschrijver: start periode 2 (ronde 7) |
| 10 | Arjen Meijeringh | 1600 | 8770278 | |
| 11 | Kevin van Oosten | 1434 | 9079301 | |
| 12 | Bastiaan van Os | 1786 | 7842054 | |
| 13 | Jan van Os | 1920 | 6036107 | |
| 14 | Sergei Sarkisyan | 1764 | 8728346 | |
| 15 | Ton Selten | 1794 | 7447957 | |
| 16 | Hans Smit | 1879 | 8546252 | |
| 17 | Marcel Struik | 1951 | 8062824 | |
| 18 | Wesley Tabak | 1745 | 8840392 | |
| 19 | Douwe Többen | 1804 | 6096783 | |
| 20 | Herman Voss | 1965 | 6892083 | |
| 21 | Flip Werter | 1553 | 6165071 | |
| 22 | Jan van Wieren | 1775 | 7692993 | |

### Late inschrijvers

- **Jarno Koopman** (id 9): stapt in bij ronde 7 (start periode 2). Geen ELO, geen KNSB-nummer. Krijgt startpunten voor 6 gemiste rondes.
- **Frank Kieft** (id 8): stapt in bij ronde 13 (start periode 3). Geen ELO, geen KNSB-nummer. Krijgt startpunten voor 12 gemiste rondes.

---

## Simulatieparameters

| Parameter | Waarde |
|---|---|
| Aanwezigheid per ronde | 14–20 spelers (driehoeksverdeling, voorkeur even) |
| Externe partijen | ~10% van aanwezigen per ronde |
| Resultaatbepaling | ELO-gewogen logistisch model |
| Basisremisekans (gelijke sterkte) | 30% |
| Remisekans bij 1000 ELO-verschil | 5% |

---

## Stand na ronde 20

| Pos | Naam | Punten | p | g | r | v | e | o | a |
|---|---|---|---|---|---|---|---|---|---|
| 1 | Douwe Többen | 900,0 | 12 | 11 | 1 | 0 | 5 | 0 | 3 |
| 2 | Herman Voss | 626,0 | 10 | 6 | 2 | 2 | 4 | 0 | 6 |
| 3 | Jan Willem Brinks | 593,5 | 13 | 7 | 2 | 4 | 2 | 0 | 5 |
| 4 | Hans Smit | 584,0 | 15 | 7 | 3 | 5 | 1 | 0 | 4 |
| 5 | Rienk Hoogeveen | 580,0 | 15 | 6 | 4 | 5 | 2 | 0 | 3 |
| 6 | Raymond Hof | 574,5 | 16 | 6 | 7 | 3 | 0 | 0 | 4 |
| 7 | Bastiaan van Os | 561,0 | 14 | 7 | 3 | 4 | 1 | 0 | 5 |
| 8 | Jan van Wieren | 537,5 | 15 | 8 | 1 | 6 | 2 | 0 | 3 |
| 9 | Marcel Struik | 532,5 | 14 | 7 | 1 | 6 | 2 | 0 | 4 |
| 10 | Ton Selten | 529,0 | 15 | 6 | 1 | 8 | 4 | 0 | 1 |
| 11 | Willem Boontje | 521,5 | 16 | 8 | 2 | 6 | 1 | 0 | 3 |
| 12 | Evert Hondema | 511,5 | 17 | 8 | 1 | 8 | 0 | 0 | 3 |
| 13 | Sergei Sarkisyan | 482,0 | 13 | 5 | 2 | 6 | 2 | 0 | 5 |
| 14 | Jan van Os | 475,0 | 13 | 4 | 3 | 6 | 3 | 0 | 4 |
| 15 | Wesley Tabak | 471,5 | 15 | 5 | 4 | 6 | 3 | 0 | 2 |
| 16 | Flip Werter | 396,0 | 13 | 3 | 3 | 7 | 3 | 0 | 4 |
| 17 | Stef Dubbeldam | 395,5 | 14 | 5 | 2 | 7 | 0 | 0 | 6 |
| 18 | Arjen Meijeringh | 391,5 | 12 | 4 | 3 | 5 | 1 | 0 | 7 |
| 19 | Kevin van Oosten | 388,0 | 15 | 6 | 1 | 8 | 1 | 0 | 4 |
| 20 | Jarno Koopman | 309,5 | 8 | 1 | 1 | 6 | 1 | 1 | 4 |
| 21 | Frank Kieft | 302,0 | 7 | 2 | 1 | 4 | 0 | 0 | 1 |
| 22 | Gert Nutterts | 224,0 | 16 | 3 | 0 | 13 | 0 | 1 | 3 |

*Kolommen: p=gespeeld, g=gewonnen, r=remise, v=verloren, e=extern, o=bye, a=afwezig*

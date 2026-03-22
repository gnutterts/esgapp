# Project Overzicht

## Achtergrond

ESG (Emmer Schaak Genootschap) heeft een interne competitie die loopt van september tot mei: 24 rondes, verdeeld over 4 perioden van 6 rondes. Periode 1 gebruikt Swiss-indelingen, perioden 2-4 Keizer-indelingen. Punten worden altijd berekend met het Keizersysteem, ongeacht de indelingsmethode.

Voorheen werden hiervoor drie losse tools gebruikt:

1. **Seville** — Windows-programma voor Swiss-indelingen (periode 1)
2. **Keiser Systeem** — Windows-programma voor Keizer-indelingen (perioden 2-4)
3. **Google Sheet** — Puntenberekening en standenlijst

esgapp.nl vervangt alle drie. De applicatie is live sinds seizoen 2025-2026.

---

## Clubregels

De volgende clubregels zijn relevant voor de applicatie:

- Spelers melden zich aan/af per ronde, of zetten auto-deelname aan
- Bij oneven aantal spelers krijgt de laagst gerankte speler een bye
- Later instromende spelers krijgen startpunten als compensatie
- Minimaal 7 keer meedoen voor de eindresultaten (clubregel, niet afgedwongen in code)
- Maximaal 5 keer afwezigheidspunten (20 punten per keer), daarna 0
- Spelers kunnen buiten de clubavond extern spelen; de wedstrijdleider moet dit bevestigen
- Onbevestigde externe partijen worden als "voorlopig" getoond in de stand

---

## Technisch

### Keuzes

- Server-side rendering met Blade, minimale JavaScript, geen SPA
- Passwordless auth via 6-cijferige e-mailcodes (SHA-256 gehasht)
- Standen worden altijd volledig herberekend, nooit incrementeel
- Alle multi-stap schrijfoperaties in database-transacties
- UI en routes in het Nederlands, modelklassen in het Engels

### Infrastructuur

- **Hosting**: Plesk op een gedeelde server (esgapp.nl)
- **Database**: MySQL — `esgapp` (productie) en `esgapp_dev` (ontwikkeling)
- **Mail**: sendmail via Plesk (productie), log-driver (ontwikkeling)
- **Queue**: Database-driver, Plesk cron elke minuut (alleen productie)
- **Scheduler**: Plesk cron (`schedule:run` elke minuut)
- **Assets**: Vite build, output in `public/build/`
- **Versiebeheer**: Git, GitHub (`gnutterts/esgapp`), branches `main` (productie) en `dev` (ontwikkeling)
- **Ontwikkelomgeving**: dev.esgapp.nl, eigen database, HTTP Basic Auth

### Bekende beperkingen

- **Config cache en tests**: Met actieve config cache worden PHPUnit `.env`-overrides genegeerd — tests draaien dan tegen de productiedatabase. Altijd `config:clear` voor tests, `config:cache` erna.
- **Minimale deelname**: De 7-rondes regel zit niet in de code. Alle spelers staan in de stand.
- **Inactieve spelers**: De UI verbergt registratieknoppen, maar de `RegistrationController` controleert `is_active` niet server-side.
- **Swiss volgnummer**: Wordt elke ronde opnieuw afgeleid uit ELO-rating. Kan verschuiven als een rating tussentijds verandert (zeldzaam).

---

## Workflow

Wijzigingen op `dev` branch (dev.esgapp.nl), testen, dan mergen naar `main` en pullen op productie.

```bash
# Dev
php artisan test
git add . && git commit -m "..." && git push origin dev

# Productie (na merge dev → main)
git pull origin main
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:clear
```

## Documentatie

```
doc/                          Publiek (geserveerd via /documentatie)
  gebruikershandleiding.md      Slug: handleiding
  keizer-puntensysteem.md       Slug: puntensysteem
  indelingsalgoritmen.md        Slug: indelingsalgoritmen
  wedstrijdleider-handleiding.md  Slug: wedstrijdleider (alleen wedstrijdleider-rol)

doc/dev/                      Ontwikkelaarsdocs (nooit publiek)
  README.md                     Index
  architectuur.md               Technische architectuur, database schema, models, services
  testing.md                    Teststrategie, alle 72 tests beschreven
  api-routes.md                 Routeoverzicht met middleware en beschrijvingen
  deployment.md                 Installatie, configuratie, troubleshooting
  testdata.md                   Referentie voor gesimuleerd testseizoen
  knsb-rating.md                KNSB-ratingverwerking specificatie
  project-overview.md           Dit bestand

README.md                     Projectkaart (GitHub)
```

De `DocumentatieController` leest uit `base_path('doc/')`. Alleen bestanden in de `PAGES` constant worden geserveerd. Bestanden met een `'role'` key zijn beperkt tot gebruikers met die rol.

## Sponsor

De applicatie wordt gesponsord door **Interio Shops** (logo in de footer). Het logo is tekst-gebaseerd: "Interio" vet donker, "Shops" regulier gewicht grijs, strakke letterafstand.

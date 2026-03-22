# ESGapp

Competitie- en toernooibeheer voor [schaakclub ESG](https://esgapp.nl). Vervangt Seville, Keiser Systeem en Google Sheets door één systeem voor indelingen, uitslagen en standen.

## Stack

- PHP 8.4 / Laravel 12
- MySQL (MariaDB)
- Tailwind CSS v4 / Blade
- Vite

## Installatie

```bash
cp .env.example .env

composer install
npm install

php artisan key:generate
php artisan migrate --seed
npm run build
```

## Testen

```bash
php artisan config:clear && php artisan test
```

Tests draaien op SQLite in-memory (zie `phpunit.xml`).

## Documentatie

- `doc/` — Publieke documentatie (geserveerd via `/documentatie`)
- `doc/dev/` — Ontwikkelaarsdocumentatie

## Licentie

Copyright (C) 2025 Gert Nutterts

Gelicenseerd onder de [AGPL-3.0](LICENSE) met [Commons Clause](https://commonsclause.com/) — je mag de software vrij gebruiken, aanpassen en verspreiden, maar niet commercieel verkopen. Zie [LICENSE](LICENSE) (Engels) of [LICENSE.nl](LICENSE.nl) (Nederlands).

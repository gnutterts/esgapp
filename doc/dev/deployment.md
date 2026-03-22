# Deployment & Configuratie

## Systeemvereisten

- PHP 8.4
- Node.js 18+ (getest met 24)
- MySQL 8+
- Composer 2+
- Plesk (webhost)

## Server setup

### Plesk configuratie

- **Webroot**: wijst naar `public/` (standaard Plesk instelling)
- **PHP versie**: 8.4 via Plesk PHP selector
- **Node.js**: beschikbaar via `.nodenv`
- **Sendmail**: beschikbaar via Plesk mailserver

### Directory structuur

```
/var/www/vhosts/esgapp.nl/
├── .git/                   # Git repository
├── app/                    # Laravel applicatiecode
├── bootstrap/              # Laravel bootstrap
├── config/                 # Configuratiebestanden
├── database/
│   ├── migrations/         # Database migraties
│   └── seeders/            # Database seeders
├── dev.esgapp.nl/          # Ontwikkelomgeving (apart git clone)
├── doc/                    # Publieke documentatie (geserveerd via /documentatie)
│   └── dev/                # Ontwikkelaarsdocumentatie (nooit publiek)
├── node_modules/           # NPM packages
├── public/                 # Webroot (Plesk wijst hierheen)
│   └── build/              # Gecompileerde Vite assets
├── resources/
│   ├── css/                # Tailwind CSS bronbestanden
│   ├── js/                 # JavaScript bronbestanden
│   └── views/              # Blade templates
├── routes/                 # Route definities
├── storage/                # Logs, cache, sessies
├── tests/                  # PHPUnit tests
├── vendor/                 # Composer packages
├── .env                    # Omgevingsconfiguratie (niet in git)
├── .gitignore
├── composer.json
├── package.json
└── vite.config.js
```

## Installatie

### Eerste installatie

```bash
# 1. Installeer PHP dependencies
composer install --optimize-autoloader --no-dev

# 2. Installeer Node dependencies en build assets
npm install
npm run build

# 3. Configureer .env
cp .env.example .env    # of handmatig aanmaken
php artisan key:generate

# 4. Bewerk .env met juiste waarden (zie Configuratie hieronder)

# 5. Database migraties en seed data
php artisan migrate --force
php artisan db:seed --force

# 6. Cache configuratie en routes
php artisan config:cache
php artisan route:cache
```

### Updates deployen

```bash
# 1. Pull nieuwe code
git pull origin main

# 2. Installeer dependencies (indien gewijzigd)
composer install --optimize-autoloader --no-dev
npm install

# 3. Build assets
npm run build

# 4. Voer migraties uit
php artisan migrate --force

# 5. Ververs caches
php artisan config:cache
php artisan route:cache
php artisan view:clear
```

## Configuratie (.env)

### Verplichte instellingen

```env
# Applicatie
APP_NAME="ESGapp"
APP_ENV=production
APP_KEY=                          # Genereer met: php artisan key:generate
APP_DEBUG=false                   # NOOIT true in productie
APP_URL=https://esgapp.nl

# Taal
APP_LOCALE=nl
APP_FALLBACK_LOCALE=nl

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esgapp
DB_USERNAME=esgapp
DB_PASSWORD="..."                 # Database wachtwoord

# Sessie
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# E-mail
MAIL_MAILER=sendmail
MAIL_FROM_ADDRESS="noreply@esgapp.nl"
MAIL_FROM_NAME="${APP_NAME}"
```

### Optionele instellingen

```env
# Cache en queue
CACHE_STORE=database              # of file, redis
QUEUE_CONNECTION=database         # of sync, redis

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning                 # error, warning, info, debug
```

**Let op**: De applicatie-tijdzone is ingesteld op `Europe/Amsterdam` in `config/app.php`.

## Database

### Initialisatie

```bash
# Volledige reset (WIST ALLE DATA):
php artisan migrate:fresh --force --seed

# Alleen migraties uitvoeren (behoudt data):
php artisan migrate --force

# Alleen seeder uitvoeren:
php artisan db:seed --force
```

### Seeder data

De seeder maakt aan:
1. **Wedstrijdleider account**: Gert Nutterts (goferhout@gmail.com)
2. **Keizer configuratie**: 9 standaardwaarden in de `settings` tabel

### Backup

Maak regelmatig backups van de MySQL database:

```bash
mysqldump -u esgapp -p esgapp > backup_$(date +%Y%m%d).sql
```

## E-mail configuratie

De applicatie gebruikt sendmail (Plesk mailserver) voor het versturen van inlogcodes.

- **Afzender**: noreply@esgapp.nl
- **Test**: Stuur een inlogcode naar jezelf via de loginpagina

Als sendmail niet werkt, kan SMTP worden geconfigureerd:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.voorbeeld.nl
MAIL_PORT=587
MAIL_USERNAME=noreply@esgapp.nl
MAIL_PASSWORD="..."
MAIL_ENCRYPTION=tls
```

## Productie caches

Na elke wijziging aan configuratie of routes:

```bash
php artisan config:cache     # Configuratie cachen
php artisan route:cache      # Routes cachen
php artisan view:clear       # Gecompileerde views wissen
```

**Belangrijk**: Na het wijzigen van `.env` ALTIJD `php artisan config:cache` uitvoeren, anders worden de oude waarden gebruikt.

## Ontwikkelomgeving (dev.esgapp.nl)

De ontwikkelomgeving draait als subdomain met een eigen database en configuratie.

### Opzet

| Onderdeel | Productie | Ontwikkeling |
|---|---|---|
| URL | https://esgapp.nl | https://dev.esgapp.nl |
| Branch | `main` | `dev` |
| Database | `esgapp` | `esgapp_dev` |
| APP_ENV | production | local |
| APP_DEBUG | false | true |
| MAIL_MAILER | sendmail | log |
| LOG_LEVEL | warning | debug |
| Toegang | Publiek | HTTP Basic Auth |

### Locatie

De ontwikkelomgeving staat in `/var/www/vhosts/esgapp.nl/dev.esgapp.nl/` — een apart git clone op de `dev` branch. Plesk webroot wijst naar `dev.esgapp.nl/public/`.

### Database synchroniseren

Om de ontwikkeldatabase te verversen met productiedata:

```bash
mysqldump -u esgapp -p esgapp | mysql -u esgapp -p esgapp_dev
```

### HTTP Basic Auth

De dev-site is beveiligd met HTTP Basic Auth via `.htaccess` en `.htpasswd` in de `dev.esgapp.nl/` directory. Dit zijn lokale bestanden, niet in git.

## Versiebeheer

De codebase wordt beheerd via git met een privé GitHub repository en twee long-lived branches.

- **Remote**: `origin` → `https://github.com/gnutterts/esgapp.git`
- **Branches**: `main` (productie), `dev` (ontwikkeling)
- **Credentials**: Opgeslagen via `git config credential.helper store` (~/.git-credentials)

### Branches

| Branch | Omgeving | Doel |
|---|---|---|
| `main` | esgapp.nl | Stabiele productie-code |
| `dev` | dev.esgapp.nl | Actieve ontwikkeling |

### Workflow

```
dev.esgapp.nl  →  commit + push dev  →  GitHub (dev branch)
                                              │
                            merge dev → main (einde ontwikkelcyclus)
                                              │
esgapp.nl      ←  pull main  ←───────────────┘
```

### Dagelijks ontwikkelen (op dev.esgapp.nl)

```bash
# Wijzigingen maken, testen, committen
php artisan test
git add . && git commit -m "..."
git push origin dev
```

### Deployen naar productie (einde ontwikkelcyclus)

```bash
# 1. Merge dev naar main (op dev.esgapp.nl of productie)
git checkout main
git merge dev
git push origin main

# 2. Op esgapp.nl: pull en deploy
git pull origin main
composer install --optimize-autoloader --no-dev   # indien dependencies gewijzigd
npm install && npm run build                       # indien frontend gewijzigd
php artisan migrate --force                        # indien migraties toegevoegd
php artisan config:cache && php artisan route:cache && php artisan view:clear

# 3. Terug naar dev branch (op dev.esgapp.nl)
git checkout dev
```

## Geplande taken (Scheduler)

De applicatie heeft een dagelijkse taak voor het opruimen van verlopen inlogcodes:

```bash
# Handmatig uitvoeren:
php artisan magic-links:clean

# De scheduler moet draaien via een cron job:
* * * * * cd /var/www/vhosts/esgapp.nl && php artisan schedule:run >> /dev/null 2>&1
```

Voeg deze cron job toe via Plesk → Scheduled Tasks.

### Queue worker

De KNSB-import (historische ratings ophalen bij nieuw KNSB-nummer) draait als queued job. Voeg een tweede Plesk scheduled task toe die elke minuut draait:

```bash
php /var/www/vhosts/esgapp.nl/artisan queue:work database --stop-when-empty --max-time=300
```

- `--stop-when-empty`: stopt zodra er geen jobs meer zijn (voorkomt permanent draaiend proces)
- `--max-time=300`: maximaal 5 minuten per run (voorkomt zombieprocessen)
- De taak wordt elke minuut gestart door Plesk; als er geen jobs in de queue staan, stopt hij direct

## Troubleshooting

### Veelvoorkomende problemen

**500 error op de homepage:**
```bash
# Controleer of de storage directory schrijfbaar is
chmod -R 775 storage bootstrap/cache
# Controleer de logbestanden
tail -50 storage/logs/laravel.log
```

**E-mail wordt niet verstuurd:**
```bash
# Test sendmail
echo "Test" | sendmail -v jouw@email.nl
# Of schakel tijdelijk naar log-driver
# In .env: MAIL_MAILER=log
# Check storage/logs/laravel.log voor de e-mail inhoud
```

**Database connectie mislukt:**
```bash
# Test connectie
php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"
```

**Assets laden niet (CSS/JS):**
```bash
# Herbouw assets
npm run build
# Controleer of public/build/ bestaat en bestanden bevat
ls -la public/build/assets/
```

**Routes geven 404:**
```bash
# Vernieuw route cache
php artisan route:cache
# Controleer of routes bestaan
php artisan route:list
```

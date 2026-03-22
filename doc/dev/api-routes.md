# Routes Overzicht

## Publieke routes (geen authenticatie)

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/` | HomeController@index | home | Homepage met top-10 stand en volgende ronde |
| GET | `/stand` | PublicController@stand | stand | Volledige actuele stand |
| GET | `/indeling/laatste` | PublicController@indelingLatest | indeling.latest | Redirect naar laatste indeling |
| GET | `/indeling/{round}` | PublicController@indeling | indeling | Bordindeling voor een ronde |
| GET | `/uitslag/laatste` | PublicController@uitslagLatest | uitslag.latest | Redirect naar laatste uitslag |
| GET | `/uitslag/{round}` | PublicController@uitslag | uitslag | Uitslagen en stand na een ronde |

## Authenticatie routes

| Methode | URL | Controller | Naam | Middleware | Beschrijving |
|---|---|---|---|---|---|
| GET | `/login` | MagicLinkController@showLoginForm | login | — | Loginformulier |
| POST | `/login` | MagicLinkController@sendLoginCode | login.send | throttle:5,1 | Inlogcode versturen |
| GET | `/login/verifieer` | MagicLinkController@showVerifyForm | login.verify | — | Code invoeren formulier |
| POST | `/login/verifieer` | MagicLinkController@authenticate | login.authenticate | throttle:5,1 | Code valideren en inloggen |
| POST | `/uitloggen` | MagicLinkController@logout | logout | — | Uitloggen |

**Beveiligingsmaatregelen:**
- Login POST en code-verificatie zijn beperkt tot 5 requests per minuut (rate limiting)
- Onbekend e-mailadres geeft dezelfde redirect als een geldig adres (voorkomt user enumeration)
- Inlogcodes (6-cijferig) worden gehashed (SHA-256) opgeslagen in de database
- Bij een nieuwe login-aanvraag worden bestaande ongebruikte codes voor dezelfde gebruiker ongeldig gemaakt
- Na inloggen wordt de sessie geregenereerd; bij uitloggen wordt de sessie volledig ongeldig gemaakt

## Publieke rating routes

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/ratings` | RatingController@index | ratings | Ratingoverzicht (KNSB, gefilterd op show_knsb_rating) |
| GET | `/ratings/{speler}` | RatingController@show | ratings.show | Individuele ratingpagina met historie |

## Publieke documentatie routes

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/documentatie` | DocumentatieController@index | documentatie.index | Documentatie overzicht |
| GET | `/documentatie/{slug}` | DocumentatieController@show | documentatie.show | Documentatie pagina |

Beschikbare slugs:

| Slug | Bestand | Toegang |
|---|---|---|
| `handleiding` | `doc/gebruikershandleiding.md` | Publiek |
| `puntensysteem` | `doc/keizer-puntensysteem.md` | Publiek |
| `indelingsalgoritmen` | `doc/indelingsalgoritmen.md` | Publiek |
| `wedstrijdleider` | `doc/wedstrijdleider-handleiding.md` | Alleen wedstrijdleider (403 voor anderen) |

Toegangscontrole wordt geregeld via de `PAGES` constant in `DocumentatieController`. Pagina's met een `'role'` key zijn alleen zichtbaar voor ingelogde gebruikers met die rol. De index-pagina filtert ook op rol: role-restricted pagina's worden niet getoond aan onbevoegde gebruikers. Niet-bestaande slugs geven 404.

## Speler routes (auth middleware)

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/dashboard` | DashboardController@index | dashboard | Speler dashboard |
| POST | `/registratie/{round}` | RegistrationController@toggle | registration.toggle | Beschikbaarheid wisselen |
| POST | `/auto-deelname` | RegistrationController@toggleAutoParticipate | auto-participate.toggle | Auto-deelname in/uitschakelen |
| POST | `/rating-zichtbaarheid/{type}` | RatingController@toggleShowRating | rating.toggle-show | KNSB-rating zichtbaarheid wisselen (type: knsb) |

## Beheer routes (auth + wedstrijdleider middleware)

Alle routes hebben prefix `/beheer` en naamprefix `beheer.`.

### Seizoenen

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/beheer/seizoenen` | SeizoenController@index | beheer.seizoenen.index | Overzicht seizoenen |
| GET | `/beheer/seizoenen/nieuw` | SeizoenController@create | beheer.seizoenen.create | Nieuw seizoen formulier |
| POST | `/beheer/seizoenen` | SeizoenController@store | beheer.seizoenen.store | Seizoen opslaan |
| GET | `/beheer/seizoenen/{seizoen}` | SeizoenController@show | beheer.seizoenen.show | Seizoen detail |
| GET | `/beheer/seizoenen/{seizoen}/bewerken` | SeizoenController@edit | beheer.seizoenen.edit | Seizoen bewerken formulier |
| PUT | `/beheer/seizoenen/{seizoen}` | SeizoenController@update | beheer.seizoenen.update | Seizoen bijwerken |
| DELETE | `/beheer/seizoenen/{seizoen}` | SeizoenController@destroy | beheer.seizoenen.destroy | Seizoen verwijderen (cascadeert naar periodes, rondes, indelingen, uitslagen) |

### Rondes

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/beheer/rondes` | RondeController@index | beheer.rondes.index | Overzicht rondes |
| GET | `/beheer/rondes/nieuw` | RondeController@create | beheer.rondes.create | Nieuwe ronde formulier |
| POST | `/beheer/rondes` | RondeController@store | beheer.rondes.store | Ronde opslaan |
| GET | `/beheer/rondes/{ronde}` | RondeController@show | beheer.rondes.show | Ronde detail |
| POST | `/beheer/rondes/{ronde}/registratie-sluiten` | RondeController@closeRegistration | beheer.rondes.close-registration | Registratie sluiten |
| POST | `/beheer/rondes/{ronde}/indeling-genereren` | RondeController@generatePairing | beheer.rondes.generate-pairing | Indeling genereren |
| GET | `/beheer/rondes/{ronde}/indeling-aanpassen` | RondeController@editPairing | beheer.rondes.edit-pairing | Indeling bewerken |
| PUT | `/beheer/rondes/{ronde}/indeling-aanpassen` | RondeController@updatePairing | beheer.rondes.update-pairing | Indeling opslaan |
| POST | `/beheer/rondes/{ronde}/indeling-definitief` | RondeController@finalizePairing | beheer.rondes.finalize-pairing | Indeling publiceren |
| POST | `/beheer/rondes/{ronde}/indeling/{pairing}/wissel-kleur` | RondeController@swapColors | beheer.rondes.swap-colors | Kleuren wisselen |
| GET | `/beheer/rondes/{ronde}/resultaten` | RondeController@showResults | beheer.rondes.results | Resultaten formulier |
| POST | `/beheer/rondes/{ronde}/resultaten` | RondeController@storeResults | beheer.rondes.store-results | Resultaten opslaan |
| POST | `/beheer/rondes/{ronde}/afronden` | RondeController@completeRound | beheer.rondes.complete | Ronde afronden |

### Spelers

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/beheer/spelers` | SpelerController@index | beheer.spelers.index | Overzicht spelers |
| GET | `/beheer/spelers/nieuw` | SpelerController@create | beheer.spelers.create | Nieuwe speler formulier |
| POST | `/beheer/spelers` | SpelerController@store | beheer.spelers.store | Speler opslaan |
| GET | `/beheer/spelers/{speler}` | SpelerController@edit | beheer.spelers.edit | Speler bewerken |
| PUT | `/beheer/spelers/{speler}` | SpelerController@update | beheer.spelers.update | Speler bijwerken |
| POST | `/beheer/spelers/{speler}/toggle-active` | SpelerController@toggleActive | beheer.spelers.toggle-active | Actief/inactief wisselen |

### Instellingen

| Methode | URL | Controller | Naam | Beschrijving |
|---|---|---|---|---|
| GET | `/beheer/instellingen` | InstellingenController@index | beheer.instellingen | Instellingen formulier |
| PUT | `/beheer/instellingen` | InstellingenController@update | beheer.instellingen.update | Instellingen opslaan |

## Route model binding

- `{round}` → `App\Models\Round`
- `{seizoen}` → `App\Models\Season`
- `{ronde}` → `App\Models\Round`
- `{speler}` → `App\Models\User`
- `{pairing}` → `App\Models\Pairing`

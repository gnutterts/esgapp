<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Beheer\InstellingenController;
use App\Http\Controllers\Beheer\RondeController;
use App\Http\Controllers\Beheer\SeizoenController;
use App\Http\Controllers\Beheer\SpelerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentatieController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Public competition pages
Route::get('/stand', [PublicController::class, 'stand'])->name('stand');
Route::get('/indeling/laatste', [PublicController::class, 'indelingLatest'])->name('indeling.latest');
Route::get('/indeling/{round}', [PublicController::class, 'indeling'])->name('indeling');
Route::get('/uitslag/laatste', [PublicController::class, 'uitslagLatest'])->name('uitslag.latest');
Route::get('/uitslag/{round}', [PublicController::class, 'uitslag'])->name('uitslag');

// Auth routes
Route::get('/login', [MagicLinkController::class, 'showLoginForm'])->name('login');
Route::post('/login', [MagicLinkController::class, 'sendLoginCode'])->name('login.send')->middleware('throttle:5,1');
Route::get('/login/verifieer', [MagicLinkController::class, 'showVerifyForm'])->name('login.verify');
Route::post('/login/verifieer', [MagicLinkController::class, 'authenticate'])->name('login.authenticate')->middleware('throttle:5,1');
Route::post('/uitloggen', [MagicLinkController::class, 'logout'])->name('logout');

// Public ratings
Route::get('/ratings', [RatingController::class, 'index'])->name('ratings');
Route::get('/ratings/{speler}', [RatingController::class, 'show'])->name('ratings.show');

// Public documentation
Route::get('/documentatie', [DocumentatieController::class, 'index'])->name('documentatie.index');
Route::get('/documentatie/{slug}', [DocumentatieController::class, 'show'])->name('documentatie.show');

// Player dashboard & registration (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/registratie/{round}', [RegistrationController::class, 'toggle'])->name('registration.toggle');
    Route::post('/auto-deelname', [RegistrationController::class, 'toggleAutoParticipate'])->name('auto-participate.toggle');
    Route::post('/rating-zichtbaarheid/{type}', [RatingController::class, 'toggleShowRating'])
        ->where('type', 'knsb')
        ->name('rating.toggle-show');
});

Route::middleware(['auth', 'wedstrijdleider'])->group(function () {
    Route::get('/beheer', fn () => redirect()->route('beheer.rondes.index'))->name('beheer');
});

Route::middleware(['auth', 'wedstrijdleider'])->prefix('beheer')->name('beheer.')->group(function () {
    // Seizoenen
    Route::get('/seizoenen', [SeizoenController::class, 'index'])->name('seizoenen.index');
    Route::get('/seizoenen/nieuw', [SeizoenController::class, 'create'])->name('seizoenen.create');
    Route::post('/seizoenen', [SeizoenController::class, 'store'])->name('seizoenen.store');
    Route::get('/seizoenen/{seizoen}', [SeizoenController::class, 'show'])->name('seizoenen.show');
    Route::get('/seizoenen/{seizoen}/bewerken', [SeizoenController::class, 'edit'])->name('seizoenen.edit');
    Route::put('/seizoenen/{seizoen}', [SeizoenController::class, 'update'])->name('seizoenen.update');
    Route::delete('/seizoenen/{seizoen}', [SeizoenController::class, 'destroy'])->name('seizoenen.destroy');

    // Rondes
    Route::get('/rondes', [RondeController::class, 'index'])->name('rondes.index');
    Route::get('/rondes/nieuw', [RondeController::class, 'create'])->name('rondes.create');
    Route::post('/rondes', [RondeController::class, 'store'])->name('rondes.store');
    Route::get('/rondes/{ronde}', [RondeController::class, 'show'])->name('rondes.show');
    Route::post('/rondes/{ronde}/registratie-sluiten', [RondeController::class, 'closeRegistration'])->name('rondes.close-registration');
    Route::post('/rondes/{ronde}/indeling-genereren', [RondeController::class, 'generatePairing'])->name('rondes.generate-pairing');
    Route::post('/rondes/{ronde}/indeling-definitief', [RondeController::class, 'finalizePairing'])->name('rondes.finalize-pairing');
    Route::get('/rondes/{ronde}/indeling-aanpassen', [RondeController::class, 'editPairing'])->name('rondes.edit-pairing');
    Route::put('/rondes/{ronde}/indeling-aanpassen', [RondeController::class, 'updatePairing'])->name('rondes.update-pairing');
    Route::post('/rondes/{ronde}/indeling/{pairing}/wissel-kleur', [RondeController::class, 'swapColors'])->name('rondes.swap-colors');
    Route::get('/rondes/{ronde}/resultaten', [RondeController::class, 'showResults'])->name('rondes.results');
    Route::post('/rondes/{ronde}/resultaten', [RondeController::class, 'storeResults'])->name('rondes.store-results');
    Route::post('/rondes/{ronde}/afronden', [RondeController::class, 'completeRound'])->name('rondes.complete');

    // Spelers
    Route::get('/spelers', [SpelerController::class, 'index'])->name('spelers.index');
    Route::get('/spelers/nieuw', [SpelerController::class, 'create'])->name('spelers.create');
    Route::post('/spelers', [SpelerController::class, 'store'])->name('spelers.store');
    Route::get('/spelers/{speler}', [SpelerController::class, 'edit'])->name('spelers.edit');
    Route::put('/spelers/{speler}', [SpelerController::class, 'update'])->name('spelers.update');
    Route::post('/spelers/{speler}/toggle-active', [SpelerController::class, 'toggleActive'])->name('spelers.toggle-active');

    // Instellingen
    Route::get('/instellingen', [InstellingenController::class, 'index'])->name('instellingen');
    Route::put('/instellingen', [InstellingenController::class, 'update'])->name('instellingen.update');
});

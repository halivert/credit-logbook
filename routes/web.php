<?php

use App\CreditCards\CreditCardController;
use App\Purchases\PurchaseController;
use App\Reconciliations\ReconciliationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityController;
use App\Payments\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// URLs in Spanish (without accents, they break when copied); route names
// stay in English like the rest of the code.
Route::resourceVerbs([
    'create' => 'crear',
    'edit' => 'editar',
]);

Route::middleware('auth')->group(function () {
    /**
     * Profile
     */
    Route::get('/perfil', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/perfil', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/perfil', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::get('/perfil/seguridad', [SecurityController::class, 'edit'])
        ->middleware('password.confirm')
        ->name('profile.security');

    /**
     * Credit Cards
     */
    Route::resource('tarjetas', CreditCardController::class)
        ->names('credit-cards')
        ->parameters(['tarjetas' => 'creditCard']);

    /**
     * Purchases
     */
    Route::resource('tarjetas.compras', PurchaseController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['tarjetas' => 'creditCard', 'compras' => 'purchase'])
        ->names([
            'create' => 'credit-cards.purchases.create',
            'store' => 'credit-cards.purchases.store',
            'edit' => 'purchases.edit',
            'update' => 'purchases.update',
            'destroy' => 'purchases.destroy',
        ])
        ->shallow();

    /**
     * Reconciling with the bank's statement
     */
    Route::get('/tarjetas/{creditCard}/conciliacion', [ReconciliationController::class, 'show'])
        ->name('credit-cards.reconciliation.show');

    Route::put('/tarjetas/{creditCard}/conciliacion', [ReconciliationController::class, 'update'])
        ->name('credit-cards.reconciliation.update');

    Route::post('/tarjetas/{creditCard}/ajustes', [ReconciliationController::class, 'storeAdjustment'])
        ->name('credit-cards.adjustments.store');

    Route::delete('/ajustes/{adjustment}', [ReconciliationController::class, 'destroyAdjustment'])
        ->name('adjustments.destroy');

    /**
     * Payments
     */
    Route::resource('tarjetas.pagos', PaymentController::class)
        ->only(['create', 'store'])
        ->parameters(['tarjetas' => 'creditCard'])
        ->names([
            'create' => 'credit-cards.payments.create',
            'store' => 'credit-cards.payments.store',
        ]);
});

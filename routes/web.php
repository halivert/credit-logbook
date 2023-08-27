<?php

use App\API\CreditCard\v1\CreditCardController;
use App\API\Transaction\v1\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function (Request $request) {
    return $request->wantsJson()
        ? response()->json(null, 200)
        : view('welcome');
});

Route::middleware(['auth:sanctum'])->prefix('api')->group(function () {
    Route::prefix('v1')->group(function () {
        Route::apiResources([
            'credit-cards' => CreditCardController::class,
            'credit-cards.transactions' => TransactionController::class
        ], [
            'parameters' => [
                'credit-cards' => 'creditCard'
            ],
            'shallow' => true
        ]);
    });
});

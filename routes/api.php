<?php

use App\Http\Controllers\ConcertsController;
use App\Http\Controllers\ShowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::
middleware('json.response')
->prefix('v1')
->group(function() {
    Route::apiResource('concerts', ConcertsController::class);
    Route::get('concerts/{concert}/shows/{show}/seating', [ShowController::class, 'seating']);
    Route::post('concerts/{concert}/shows/{show}/reservation', [ShowController::class, 'reservation']);
    Route::post('concerts/{concert}/shows/{show}/booking', [ShowController::class, 'booking']);
    Route::post('tickets', [ShowController::class, 'tickets']);
    Route::post('tickets/{ticket}/cancel', [ShowController::class, 'cancel']);
});

<?php

use App\Http\Controllers\StampDutyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| GET  /           → Calculator page (Blade view)
| POST /calculate  → AJAX endpoint — returns JSON result
|
*/

Route::get('/', [StampDutyController::class, 'index'])->name('calculator');

Route::post('/calculate', [StampDutyController::class, 'calculate'])->name('calculator.calculate');

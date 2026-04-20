<?php

use App\Http\Controllers\StampDutyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StampDutyController::class, 'index']);
Route::post('/calculate', [StampDutyController::class, 'calculate'])->name('calculate');

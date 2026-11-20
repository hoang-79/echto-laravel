<?php

use Echto\Laravel\Http\Controllers\ConfirmationController;
use Echto\Laravel\Http\Controllers\LoginCodeController;
use Illuminate\Support\Facades\Route;

/*
 * Seiten für Besucher der Website: Bestätigung per E-Mail (F-08) und Code bei der Anmeldung (F-09).
 */

Route::prefix((string) config('echto.confirmation.path', 'echto/bestaetigen'))->name('echto.confirmation.')->group(function () {
    Route::get('{submission}', [ConfirmationController::class, 'show'])->name('show')->whereNumber('submission');
    Route::post('{submission}', [ConfirmationController::class, 'confirm'])->name('confirm')->whereNumber('submission')->middleware('throttle:20,1');
});

Route::prefix((string) config('echto.login.path', 'echto/code'))->name('echto.login.')->group(function () {
    Route::get('{id}', [LoginCodeController::class, 'show'])->name('show')->where('id', '[A-Za-z0-9]{32}');
    Route::post('{id}', [LoginCodeController::class, 'verify'])->name('verify')->where('id', '[A-Za-z0-9]{32}')->middleware('throttle:10,1');
});

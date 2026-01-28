<?php

use App\Http\Controllers\DomainController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard with domains
    Route::get('dashboard', [DomainController::class, 'index'])->name('dashboard');

    // Domain CRUD
    Route::prefix('domains')->name('domains.')->group(function () {
        Route::post('/', [DomainController::class, 'store'])->name('store');
        Route::get('{id}', [DomainController::class, 'show'])->name('show');
        Route::put('{id}', [DomainController::class, 'update'])->name('update');
        Route::delete('{id}', [DomainController::class, 'destroy'])->name('destroy');
        Route::post('{id}/toggle-active', [DomainController::class, 'toggleActive'])->name('toggle-active');
    });
});

require __DIR__.'/settings.php';
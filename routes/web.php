<?php

use App\Http\Controllers\PageController;
use App\Http\Middleware\AllowIframe;
use Illuminate\Support\Facades\Route;

Route::middleware(AllowIframe::class)->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/produits', [PageController::class, 'produits'])->name('produits');
    Route::get('/menus', [PageController::class, 'menus'])->name('menus');
    Route::get('/entreprises', [PageController::class, 'entreprises'])->name('entreprises');
});

Route::get('/simulateur-armes', fn () => view('simulateur-armes'))->name('simulateur-armes');

<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/produits', [PageController::class, 'produits'])->name('produits');
Route::get('/menus', [PageController::class, 'menus'])->name('menus');
Route::get('/entreprises', [PageController::class, 'entreprises'])->name('entreprises');

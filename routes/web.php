<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\WeaponSimController;
use App\Http\Middleware\AllowIframe;
use Illuminate\Support\Facades\Route;

Route::middleware(AllowIframe::class)->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/produits', [PageController::class, 'produits'])->name('produits');
    Route::get('/menus', [PageController::class, 'menus'])->name('menus');
    Route::get('/entreprises', [PageController::class, 'entreprises'])->name('entreprises');
});

Route::get('/mc', [WeaponSimController::class, 'hub'])->name('mc.hub');
Route::get('/simulateur-armes', [WeaponSimController::class, 'index'])->name('simulateur-armes');
Route::get('/simulateur-munitions', [WeaponSimController::class, 'munitions'])->name('simulateur-munitions');
Route::get('/espace-membres', [WeaponSimController::class, 'espaceMembres'])->name('espace-membres');
Route::post('/simulateur-armes/api/login', [WeaponSimController::class, 'login']);
Route::get('/simulateur-armes/api/data', [WeaponSimController::class, 'apiData']);
Route::post('/simulateur-armes/api/sale', [WeaponSimController::class, 'createSale']);
Route::post('/simulateur-armes/api/movement', [WeaponSimController::class, 'createMovement']);
Route::post('/simulateur-armes/api/contract', [WeaponSimController::class, 'createContract']);
Route::put('/simulateur-armes/api/contract/{id}', [WeaponSimController::class, 'updateContract']);
Route::put('/simulateur-armes/api/contract-item/{id}', [WeaponSimController::class, 'updateContractItem']);
Route::post('/simulateur-armes/api/member', [WeaponSimController::class, 'createMember']);
Route::put('/simulateur-armes/api/member/{id}', [WeaponSimController::class, 'updateMember']);
Route::post('/simulateur-armes/api/change-pin', [WeaponSimController::class, 'changePin']);

// Gestion des membres (front)
Route::get('/membres', [MemberController::class, 'index'])->name('membres');
Route::get('/membres/api/list', [MemberController::class, 'apiList']);
Route::post('/membres/api/create', [MemberController::class, 'apiCreate']);
Route::put('/membres/api/{id}', [MemberController::class, 'apiUpdate']);
Route::post('/membres/api/{id}/reset-pin', [MemberController::class, 'apiResetPin']);
Route::delete('/membres/api/{id}', [MemberController::class, 'apiDelete']);

// Matrice d'acces
Route::get('/membres/api/matrix', [MemberController::class, 'apiMatrix']);
Route::put('/membres/api/matrix/{id}', [MemberController::class, 'apiUpdateMatrix']);

<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DecksController;
use App\Controllers\AdminController;
use Core\Router\Route;

// Página inicial
Route::get('/', [HomeController::class, 'index'])->name('root');

// Autenticação
Route::get('/login', [AuthController::class, 'new'])->name('login');
Route::post('/login', [AuthController::class, 'create'])->name('login.create');


// rotas protegidas
Route::middleware('auth')->group(function () {
    // Autenticação
    Route::get('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Decks
    Route::get('/decks', [DecksController::class, 'index'])->name('decks');

    // Administração
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
});

<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DecksController;
use App\Controllers\CardsController;
use App\Controllers\AdminController;
use App\Controllers\RegisterController;
use Core\Router\Route;

// Página inicial
Route::get('/', [HomeController::class, 'index'])->name('root');

// Autenticação
Route::get('/login', [AuthController::class, 'new'])->name('login');
Route::post('/login', [AuthController::class, 'create'])->name('login.create');

Route::get('/register', [RegisterController::class, 'new'])->name('register');
Route::post('/register', [RegisterController::class, 'create'])->name('register.create');


// rotas protegidas
Route::middleware('auth')->group(function () {
    // Autenticação
    Route::get('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Decks
    Route::get('/decks', [DecksController::class, 'index'])->name('decks');
    Route::get('/decks/create', [DecksController::class, 'createview'])->name('decks.create');
    Route::post('/decks/create', [DecksController::class, 'create'])->name('decks.store');
    Route::get('/decks/{id}', [DecksController::class, 'show'])->name('decks.show');
    Route::get('/decks/{id}/edit', [DecksController::class, 'edit'])->name('decks.edit');
    Route::post('/decks/{id}/update', [DecksController::class, 'update'])->name('decks.update');
    Route::post('/decks/{id}/delete', [DecksController::class, 'destroy'])->name('decks.destroy');

    // Cards
    Route::get('/cards/create', [CardsController::class, 'createview'])->name('cards.create');
    Route::post('/cards/create', [CardsController::class, 'create'])->name('cards.store');
    Route::get('/cards/{id}/edit', [CardsController::class, 'edit'])->name('cards.edit');
    Route::post('/cards/{id}/update', [CardsController::class, 'update'])->name('cards.update');
    Route::post('/cards/{id}/delete', [CardsController::class, 'destroy'])->name('cards.destroy');

    // Administração
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
});

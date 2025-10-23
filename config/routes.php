<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DecksController;
use App\Controllers\CardsController;
use App\Controllers\AdminController;
use App\Controllers\RegisterController;
use Core\Router\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/register', [RegisterController::class, 'new'])->name('register.new');
Route::post('/register', [RegisterController::class, 'create'])->name('register.create');

Route::get('/login', [AuthController::class, 'new'])->name('login.new');
Route::post('/login', [AuthController::class, 'create'])->name('login.create');

Route::middleware('auth')->group(function () {
    Route::get('/logout', [AuthController::class, 'destroy'])->name('logout');

    // decks
    // create
    Route::get('/decks/new', [DecksController::class, 'new'])->name('decks.new');
    Route::post('/decks', [DecksController::class, 'create'])->name('decks.create');

    // edit
    Route::get('/decks/{id}/edit', [DecksController::class, 'edit'])->name('decks.edit');
    Route::put('/decks/{id}', [DecksController::class, 'update'])->name('decks.update');

    // delete
    Route::delete('/decks/{id}', [DecksController::class, 'destroy'])->name('decks.destroy');

    // view
    Route::get('/decks', [DecksController::class, 'index'])->name('decks.index');
    Route::get('/decks/{id}', [DecksController::class, 'show'])->name('decks.show');

    // create
    Route::get('/cards/new', [CardsController::class, 'new'])->name('cards.new');
    Route::post('/cards', [CardsController::class, 'create'])->name('cards.create');

    // edit
    Route::get('/cards/{id}/edit', [CardsController::class, 'edit'])->name('cards.edit');
    Route::put('/cards/{id}', [CardsController::class, 'update'])->name('cards.update');

    // delete
    Route::delete('/cards/{id}', [CardsController::class, 'destroy'])->name('cards.destroy');

    // view
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
});

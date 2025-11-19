<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DecksController;
use App\Controllers\CardsController;
use App\Controllers\AdminController;
use App\Controllers\RegisterController;
use App\Controllers\StudyController;
use App\Controllers\MaterialsController;
use App\Controllers\SharedDecksController;
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

    // shared decks
    Route::get('/shared-decks', [SharedDecksController::class, 'index'])->name('shared.decks.index');
    Route::post('/decks/{id}/share', [SharedDecksController::class, 'share'])->name('decks.share');
    Route::get('/shared-decks/accept/{token}', [SharedDecksController::class, 'accept'])->name('shared.decks.accept');
    Route::delete('/shared-decks/{id}', [SharedDecksController::class, 'remove'])->name('shared.decks.remove');

    // study
    Route::get('/decks/{id}/study', [StudyController::class, 'start'])->name('study.start');
    Route::get('/study/card', [StudyController::class, 'show'])->name('study.show');
    Route::get('/study/flip', [StudyController::class, 'flip'])->name('study.flip');
    Route::post('/study/answer', [StudyController::class, 'answer'])->name('study.answer');
    Route::get('/study/finish', [StudyController::class, 'finish'])->name('study.finish');

    // create
    Route::get('/cards/new', [CardsController::class, 'new'])->name('cards.new');
    Route::post('/cards', [CardsController::class, 'create'])->name('cards.create');

    // edit
    Route::get('/cards/{id}/edit', [CardsController::class, 'edit'])->name('cards.edit');
    Route::put('/cards/{id}', [CardsController::class, 'update'])->name('cards.update');

    // delete
    Route::delete('/cards/{id}', [CardsController::class, 'destroy'])->name('cards.destroy');

    // materials
    // view
    Route::get('/materials', [MaterialsController::class, 'show'])->name('materials.index');

    // create
    Route::get('/materials/new', [MaterialsController::class, 'new'])->name('materials.new');
    Route::post('/materials', [MaterialsController::class, 'create'])->name('materials.create');

    // delete
    Route::delete('/materials/{id}', [MaterialsController::class, 'destroy'])->name('materials.destroy');

    // view
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
});

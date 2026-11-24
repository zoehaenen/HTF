<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TaskController::class, 'overview'])->name('overview');
Route::get('/toevoegen', [TaskController::class, 'create'])->name('create');
Route::post('/toevoegen', [TaskController::class, 'analyse'])->name('analyse');
Route::get('/bevestigen', [TaskController::class, 'confirm'])->name('confirm');
Route::post('/bevestigen', [TaskController::class, 'store'])->name('store');
Route::post('/taken/{task}/klaar', [TaskController::class, 'toggleDone'])->name('tasks.done');
Route::post('/taken/{task}/verwijderen', [TaskController::class, 'destroy'])->name('tasks.destroy');
Route::get('/instellingen', [TaskController::class, 'settings'])->name('settings');
Route::post('/instellingen', [TaskController::class, 'updateSettings'])->name('settings.update');

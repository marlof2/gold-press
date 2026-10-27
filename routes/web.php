<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/minha-conta', [AuthController::class, 'account'])->name('account');
    Route::put('/minha-conta/senha', [AuthController::class, 'password'])->middleware('throttle:6,1')->name('account.password');
    Route::get('/clientes/{cliente}/dados', [ClienteController::class, 'dados'])->name('clientes.dados');
    Route::resource('clientes', ClienteController::class);
    Route::resource('statuses', StatusController::class)->except('show')->parameters(['statuses' => 'status']);
    Route::get('/ordens/{token}/sucesso', [OrdemServicoController::class, 'success'])->whereUuid('token')->name('ordens.success');
    Route::resource('ordens', OrdemServicoController::class)->except('show')->parameters(['ordens' => 'ordem']);
});

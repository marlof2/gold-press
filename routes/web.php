<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoOrdemController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/documentos/{token}/{formato}', [DocumentoOrdemController::class, 'show'])->whereUuid('token')->whereIn('formato', ['pdf', 'docx'])->middleware('throttle:60,1')->name('documentos.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
});
Route::middleware(['auth', 'single.session'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/minha-conta', [AuthController::class, 'account'])->name('account');
    Route::put('/minha-conta/senha', [AuthController::class, 'password'])->middleware('throttle:6,1')->name('account.password');
    Route::get('/clientes/{cliente}/dados', [ClienteController::class, 'dados'])->name('clientes.dados');
    Route::resource('clientes', ClienteController::class)->except('destroy');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->middleware('can:administrar')->name('clientes.destroy');
    Route::resource('statuses', StatusController::class)->except('show')->parameters(['statuses' => 'status'])->middleware('can:administrar');
    Route::resource('produtos', ProdutoController::class)->except('show')->parameters(['produtos' => 'produto'])->middleware('can:administrar');
    Route::get('/empresa', [EmpresaController::class, 'edit'])->middleware('can:administrar')->name('empresa.edit');
    Route::put('/empresa', [EmpresaController::class, 'update'])->middleware('can:administrar')->name('empresa.update');
    Route::resource('usuarios', UserController::class)->except('show')->parameters(['usuarios' => 'usuario'])->middleware('can:administrar');
    Route::get('/ordens/{token}/sucesso', [OrdemServicoController::class, 'success'])->whereUuid('token')->name('ordens.success');
    Route::get('/ordens/{ordem}/acoes/{acao}', [OrdemServicoController::class, 'acao'])->whereIn('acao', ['status', 'whatsapp', 'pagamentos'])->name('ordens.acao');
    Route::patch('/ordens/{ordem}/status', [OrdemServicoController::class, 'atualizarStatus'])->name('ordens.status');
    Route::post('/ordens/{ordem}/pagamentos', [PagamentoController::class, 'store'])->name('ordens.pagamentos.store');
    Route::patch('/ordens/{ordem}/pagamentos/{pagamento}/estornar', [PagamentoController::class, 'estornar'])->middleware('can:administrar')->name('ordens.pagamentos.estornar');
    Route::delete('/ordens/{ordem}', [OrdemServicoController::class, 'destroy'])->middleware('can:administrar')->name('ordens.destroy');
    Route::resource('ordens', OrdemServicoController::class)->except(['show', 'destroy'])->parameters(['ordens' => 'ordem']);
});

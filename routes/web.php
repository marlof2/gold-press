<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoOrdemController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FaturamentoController;
use App\Http\Controllers\ModeloWhatsAppController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PerfilController;
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
    Route::get('/faturamento', FaturamentoController::class)->middleware('can:faturamento.visualizar')->name('faturamento.index');
    Route::get('/', DashboardController::class)->middleware('can:dashboard.visualizar')->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/minha-conta', [AuthController::class, 'account'])->name('account');
    Route::put('/minha-conta/senha', [AuthController::class, 'password'])->middleware('throttle:6,1')->name('account.password');
    Route::get('/clientes/{cliente}/dados', [ClienteController::class, 'dados'])->middleware('can:clientes.visualizar')->name('clientes.dados');
    Route::get('/clientes/{cliente}/historico', [ClienteController::class, 'historico'])->middleware(['can:clientes.visualizar', 'can:ordens.visualizar'])->name('clientes.historico');
    Route::resource('clientes', ClienteController::class)->except('destroy')
        ->middlewareFor(['index'], 'can:viewAny,App\Models\Cliente')
        ->middlewareFor(['create', 'store'], 'can:create,App\Models\Cliente')
        ->middlewareFor(['edit', 'update'], 'can:update,cliente')
        ->middlewareFor(['show'], 'can:view,cliente');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->middleware('can:delete,cliente')->name('clientes.destroy');
    Route::resource('statuses', StatusController::class)->except('show')->parameters(['statuses' => 'status'])
        ->middlewareFor(['index'], 'can:viewAny,App\Models\Status')
        ->middlewareFor(['create', 'store'], 'can:create,App\Models\Status')
        ->middlewareFor(['edit', 'update'], 'can:update,status')
        ->middlewareFor(['destroy'], 'can:delete,status');
    Route::resource('produtos', ProdutoController::class)->except('show')->parameters(['produtos' => 'produto'])
        ->middlewareFor(['index'], 'can:viewAny,App\Models\Produto')
        ->middlewareFor(['create', 'store'], 'can:create,App\Models\Produto')
        ->middlewareFor(['edit', 'update'], 'can:update,produto')
        ->middlewareFor(['destroy'], 'can:delete,produto');
    Route::get('/perfis', [PerfilController::class, 'index'])->middleware('can:perfis.visualizar')->name('perfis.index');
    Route::get('/perfis/{perfil}/edit', [PerfilController::class, 'edit'])->middleware('can:update,perfil')->name('perfis.edit');
    Route::put('/perfis/{perfil}', [PerfilController::class, 'update'])->middleware('can:update,perfil')->name('perfis.update');
    Route::resource('mensagens-whatsapp', ModeloWhatsAppController::class)->only(['index', 'create', 'store', 'edit', 'update'])->parameters(['mensagens-whatsapp' => 'modelo'])
        ->middlewareFor(['index'], 'can:mensagens-whatsapp.visualizar')
        ->middlewareFor(['create', 'store'], 'can:mensagens-whatsapp.criar')
        ->middlewareFor(['edit', 'update'], 'can:mensagens-whatsapp.editar');
    Route::get('/empresa', [EmpresaController::class, 'edit'])->middleware('can:empresa.visualizar')->name('empresa.edit');
    Route::put('/empresa', [EmpresaController::class, 'update'])->middleware('can:empresa.editar')->name('empresa.update');
    Route::resource('usuarios', UserController::class)->except('show')->parameters(['usuarios' => 'usuario'])
        ->middlewareFor(['index'], 'can:viewAny,App\Models\User')
        ->middlewareFor(['create', 'store'], 'can:create,App\Models\User')
        ->middlewareFor(['edit', 'update'], 'can:update,usuario')
        ->middlewareFor(['destroy'], 'can:delete,usuario');
    Route::get('/ordens/{token}/sucesso', [OrdemServicoController::class, 'success'])->whereUuid('token')->middleware('can:ordens.visualizar')->name('ordens.success');
    Route::get('/ordens/{ordem}/acoes/{acao}', [OrdemServicoController::class, 'acao'])->whereIn('acao', ['status', 'whatsapp', 'pagamentos'])->middleware('can:ordens.visualizar')->name('ordens.acao');
    Route::patch('/ordens/{ordem}/status', [OrdemServicoController::class, 'atualizarStatus'])->middleware('can:ordens.status')->name('ordens.status');
    Route::post('/ordens/{ordem}/pagamentos', [PagamentoController::class, 'store'])->middleware('can:pagamentos.criar')->name('ordens.pagamentos.store');
    Route::patch('/ordens/{ordem}/pagamentos/{pagamento}/estornar', [PagamentoController::class, 'estornar'])->middleware('can:pagamentos.estornar')->name('ordens.pagamentos.estornar');
    Route::delete('/ordens/{ordem}', [OrdemServicoController::class, 'destroy'])->middleware('can:delete,ordem')->name('ordens.destroy');
    Route::resource('ordens', OrdemServicoController::class)->except(['show', 'destroy'])->parameters(['ordens' => 'ordem'])
        ->middlewareFor(['index'], 'can:viewAny,App\Models\OrdemServico')
        ->middlewareFor(['create', 'store'], 'can:create,App\Models\OrdemServico')
        ->middlewareFor(['edit', 'update'], 'can:update,ordem');
});

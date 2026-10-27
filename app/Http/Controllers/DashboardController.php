<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $abertas = OrdemServico::abertas();

        return view('dashboard', [
            'totalOrdens' => OrdemServico::count(), 'totalClientes' => Cliente::count(), 'abertas' => (clone $abertas)->count(),
            'hoje' => (clone $abertas)->prazo('hoje')->count(), 'proximas' => (clone $abertas)->prazo('proximas')->count(),
            'entregas' => (clone $abertas)->with('status')->whereNotNull('data_entrega')->whereDate('data_entrega', '<=', today()->addDays(7))->orderBy('data_entrega')->take(6)->get(),
            'atrasadas' => (clone $abertas)->whereDate('data_entrega', '<', today())->count(),
            'valorMes' => OrdemServico::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('valor_total_final'),
            'ordens' => OrdemServico::with('status')->latest()->take(6)->get(),
            'statuses' => Status::withCount('ordens')->orderBy('posicao')->get(),
        ]);
    }
}

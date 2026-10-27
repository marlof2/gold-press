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
        $abertas = OrdemServico::whereHas('status', fn ($q) => $q->where('finalizado', false));

        return view('dashboard', [
            'totalOrdens' => OrdemServico::count(), 'totalClientes' => Cliente::count(), 'abertas' => (clone $abertas)->count(),
            'atrasadas' => (clone $abertas)->whereDate('data_entrega', '<', today())->count(),
            'valorMes' => OrdemServico::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('valor_total_final'),
            'ordens' => OrdemServico::with('status')->latest()->take(6)->get(),
            'statuses' => Status::withCount('ordens')->orderBy('posicao')->get(),
        ]);
    }
}

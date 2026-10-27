<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmpresaRequest;
use App\Models\Empresa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmpresaController extends Controller
{
    public function edit(): View
    {
        return view('empresa.edit', ['empresa' => Empresa::atual()]);
    }

    public function update(EmpresaRequest $request): RedirectResponse
    {
        Empresa::atual()->fill($request->validated())->save();

        return back()->with('success', 'Dados da empresa atualizados.');
    }
}

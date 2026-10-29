<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilRequest;
use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function index(): View
    {
        return view('perfis.index', ['perfis' => Perfil::withCount('usuarios', 'permissoes')->orderBy('nome')->get()]);
    }

    public function edit(Perfil $perfil): View
    {
        return view('perfis.edit', ['perfil' => $perfil->load('permissoes'), 'grupos' => Permissao::orderBy('id')->get()->groupBy('grupo')]);
    }

    public function update(PerfilRequest $request, Perfil $perfil): RedirectResponse
    {
        DB::transaction(function () use ($request, $perfil): void {
            Perfil::orderBy('id')->lockForUpdate()->get();
            $perfil->permissoes()->sync($request->validated('permissoes'));
            $gestor = Perfil::whereHas('usuarios')
                ->whereHas('permissoes', fn ($query) => $query->where('slug', 'perfis.visualizar'))
                ->whereHas('permissoes', fn ($query) => $query->where('slug', 'perfis.editar'))->exists();
            if (! $gestor) {
                throw ValidationException::withMessages(['permissoes' => 'Mantenha pelo menos um perfil com usuário e permissão para visualizar e editar perfis.']);
            }
        });
        $request->user()->unsetRelation('perfil');

        return redirect()->route($request->user()->can('perfis.visualizar') ? 'perfis.index' : 'account')->with('success', 'Permissões do perfil atualizadas.');
    }
}

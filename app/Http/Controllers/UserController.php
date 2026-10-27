<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $usuarios = User::with('perfil')->when($filtros['q'] ?? null, function ($query, $q) {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%');
            });
        })->orderBy('name')->paginate(15)->withQueryString();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.form', ['usuario' => new User, 'perfis' => $this->perfis()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('usuarios.index')->with('success', 'Usuário cadastrado.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', ['usuario' => $usuario, 'perfis' => $this->perfis()]);
    }

    public function update(UserRequest $request, User $usuario): RedirectResponse
    {
        $dados = $request->validated();
        $senhaAlterada = filled($dados['password'] ?? null);
        if (! $senhaAlterada) {
            unset($dados['password']);
        }

        $usuario->update($dados);

        if ($senhaAlterada) {
            $token = $usuario->is($request->user()) ? Str::random(64) : null;
            if ($token) {
                $request->session()->put('single_session', $token);
            }
            $usuario->forceFill(['session_token' => $token])->save();
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'Você não pode excluir a própria conta.']);
        }
        if (User::count() <= 1) {
            return back()->withErrors(['usuario' => 'Mantenha pelo menos um usuário cadastrado.']);
        }
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuário excluído.');
    }

    private function perfis(): Collection
    {
        return Perfil::orderBy('nome')->get();
    }
}

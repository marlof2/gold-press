<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProdutoRequest;
use App\Models\Produto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:255'], 'tipo' => ['nullable', 'in:unitario,m2']]);
        $produtos = Produto::query()
            ->withCount('itens')
            ->when($filtros['q'] ?? null, fn ($query, $q) => $query->where('nome', 'like', '%'.$q.'%'))
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('produtos.index', compact('produtos'));
    }

    public function create(): View
    {
        return view('produtos.form', ['produto' => new Produto(['tipo' => 'unitario', 'unidade' => 'un', 'ativo' => true])]);
    }

    public function store(ProdutoRequest $request): RedirectResponse
    {
        Produto::create($request->validated());

        return redirect()->route('produtos.index')->with('success', 'Produto cadastrado.');
    }

    public function edit(Produto $produto): View
    {
        return view('produtos.form', compact('produto'));
    }

    public function update(ProdutoRequest $request, Produto $produto): RedirectResponse
    {
        $produto->update($request->validated());

        return redirect()->route('produtos.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        if ($produto->itens()->exists()) {
            return back()->withErrors(['produto' => 'Este produto está vinculado a itens de OS. Desative-o em vez de excluir.']);
        }

        $produto->delete();

        return redirect()->route('produtos.index')->with('success', 'Produto excluído.');
    }
}

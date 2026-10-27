<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $clientes = Cliente::query()->withCount('ordens')->when($filtros['q'] ?? null, function ($query, $q) {
            $query->where(function ($query) use ($q) {
                $query->where('nome', 'like', '%'.$q.'%')->orWhere('documento', 'like', '%'.$q.'%')->orWhere('telefone', 'like', '%'.$q.'%');
            });
        })->orderBy('nome')->paginate(15)->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function create(): View
    {
        return view('clientes.form', ['cliente' => new Cliente]);
    }

    public function store(ClienteRequest $request): RedirectResponse|JsonResponse
    {
        $cliente = Cliente::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json($cliente->only(['id', ...Cliente::DADOS_OS, 'email']), 201);
        }

        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente cadastrado.');
    }

    public function show(Cliente $cliente): View
    {
        return view('clientes.show', ['cliente' => $cliente, 'ordens' => $cliente->ordens()->with('status')->latest()->paginate(10)]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.form', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente atualizado. Os dados históricos das OS foram preservados.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        if ($cliente->ordens()->exists()) {
            return back()->withErrors(['cliente' => 'Este cliente possui OS vinculadas e não pode ser excluído.']);
        }
        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente excluído.');
    }

    public function dados(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente->only(Cliente::DADOS_OS));
    }
}

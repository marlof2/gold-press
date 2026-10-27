<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatusRequest;
use App\Models\Status;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class StatusController extends Controller
{
    public function index(): View
    {
        return view('statuses.index', ['statuses' => Status::withCount('ordens')->orderBy('posicao')->orderBy('nome')->paginate(15)]);
    }

    public function create(): View
    {
        return view('statuses.form', ['status' => new Status(['cor' => '#b08735', 'posicao' => 0])]);
    }

    public function store(StatusRequest $request): RedirectResponse
    {
        Status::create([...$request->validated(), 'slug' => Str::slug($request->string('nome')).'-'.Str::lower(Str::random(6))]);

        return redirect()->route('statuses.index')->with('success', 'Status cadastrado.');
    }

    public function edit(Status $status): View
    {
        return view('statuses.form', compact('status'));
    }

    public function update(StatusRequest $request, Status $status): RedirectResponse
    {
        $status->update($request->validated());

        return redirect()->route('statuses.index')->with('success', 'Status atualizado.');
    }

    public function destroy(Status $status): RedirectResponse
    {
        if ($status->ordens()->exists()) {
            return back()->withErrors(['status' => 'Este status possui OS vinculadas. Altere o status dessas ordens antes de excluí-lo.']);
        }
        if (Status::count() <= 1) {
            return back()->withErrors(['status' => 'Mantenha pelo menos um status cadastrado.']);
        }
        $status->delete();

        return redirect()->route('statuses.index')->with('success', 'Status excluído.');
    }
}

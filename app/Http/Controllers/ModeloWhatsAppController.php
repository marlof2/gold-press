<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModeloWhatsAppRequest;
use App\Models\ModeloWhatsApp;
use App\Services\MensagensWhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ModeloWhatsAppController extends Controller
{
    public function index(): View
    {
        return view('mensagens-whatsapp.index', ['modelos' => ModeloWhatsApp::orderBy('posicao')->orderBy('id')->paginate(15)]);
    }

    public function create(): View
    {
        return view('mensagens-whatsapp.form', ['modelo' => new ModeloWhatsApp(['posicao' => 10]), 'variaveis' => MensagensWhatsApp::VARIAVEIS, 'exemplo' => app(MensagensWhatsApp::class)->exemplo()]);
    }

    public function store(ModeloWhatsAppRequest $request): RedirectResponse
    {
        ModeloWhatsApp::create([...$request->validated(), 'chave' => 'modelo-'.Str::uuid()]);

        return redirect()->route('mensagens-whatsapp.index')->with('success', 'Mensagem cadastrada.');
    }

    public function edit(ModeloWhatsApp $modelo): View
    {
        return view('mensagens-whatsapp.form', ['modelo' => $modelo, 'variaveis' => MensagensWhatsApp::VARIAVEIS, 'exemplo' => app(MensagensWhatsApp::class)->exemplo()]);
    }

    public function update(ModeloWhatsAppRequest $request, ModeloWhatsApp $modelo): RedirectResponse
    {
        $modelo->update($request->validated());

        return redirect()->route('mensagens-whatsapp.index')->with('success', 'Mensagem atualizada.');
    }
}

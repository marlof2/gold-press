<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdemServicoRequest;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\Status;
use App\Services\SalvarOrdemServico;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrdemServicoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:255'], 'status_id' => ['nullable', 'integer', 'exists:statuses,id']]);
        $ordens = OrdemServico::with('status')->withCount('itens')->when($filtros['q'] ?? null, fn ($query, $q) => $query->where(function ($query) use ($q) {
            $query->where('nome', 'like', '%'.$q.'%')->orWhereHas('cliente', fn ($cliente) => $cliente->where('nome', 'like', '%'.$q.'%'))->orWhere('descricao', 'like', '%'.$q.'%')->orWhereHas('itens', fn ($itens) => $itens->where('descricao', 'like', '%'.$q.'%'))->orWhere('id', ltrim($q, '#0'));
        }))
            ->when($filtros['status_id'] ?? null, fn ($query, $id) => $query->where('status_id', $id))->latest()->paginate(15)->withQueryString();

        return view('ordens.index', ['ordens' => $ordens, 'statuses' => Status::orderBy('posicao')->get()]);
    }

    public function create(): View
    {
        return $this->formulario();
    }

    private function formulario(?OrdemServico $ordem = null): View
    {
        $ordem?->load('itens');

        return view('ordens.create', [
            'ordem' => $ordem,
            'clientes' => Cliente::orderBy('nome')->get(['id', 'nome', 'telefone', 'documento', 'endereco', 'cidade', 'bairro', 'uf', 'cep']),
            'statuses' => Status::orderBy('posicao')->orderBy('nome')->get(),
            'produtos' => Produto::ativos()->orderBy('nome')->get(),
        ]);
    }

    public function edit(OrdemServico $ordem): View
    {
        return $this->formulario($ordem);
    }

    public function store(StoreOrdemServicoRequest $request, SalvarOrdemServico $servico): RedirectResponse
    {
        return $this->persistir($request, $servico);
    }

    public function update(StoreOrdemServicoRequest $request, OrdemServico $ordem, SalvarOrdemServico $servico): RedirectResponse
    {
        return $this->persistir($request, $servico, $ordem);
    }

    private function persistir(StoreOrdemServicoRequest $request, SalvarOrdemServico $servico, ?OrdemServico $ordem = null): RedirectResponse
    {
        try {
            $ordem = $servico->salvar($request->validated(), $ordem);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['documento' => 'Não foi possível salvar e gerar o documento da OS. Tente novamente.']);
        }

        $mensagem = 'Ordem de serviço salva com sucesso.';
        $rota = route('ordens.success', $ordem->token);

        if ($request->input('acao') === 'salvar_docx') {
            $rota = route('ordens.success', ['token' => $ordem->token, 'baixar' => 1]);
            $mensagem = 'Ordem de serviço salva. O download do DOCX começou.';
        }

        return redirect()->to($rota)->with('success', $mensagem);
    }

    public function destroy(OrdemServico $ordem): RedirectResponse
    {
        $path = $ordem->arquivo_path;
        $ordem->delete();
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('ordens.index')->with('success', 'Ordem de serviço excluída.');
    }

    public function success(string $token): View
    {
        $ordem = OrdemServico::with('status', 'cliente', 'itens')->where('token', $token)->firstOrFail();
        $url = Storage::disk('public')->url($ordem->arquivo_path);
        $telefone = preg_replace('/\D/', '', $ordem->telefone);
        if (in_array(strlen($telefone), [10, 11])) {
            $telefone = '55'.$telefone;
        }
        $texto = 'Olá, '.$ordem->nome.'! Segue sua OS #'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT).'. Valor final: R$ '.number_format((float) $ordem->valor_total_final, 2, ',', '.').'. Baixe o documento: '.$url;
        $whatsappUrl = 'https://wa.me/'.$telefone.'?text='.rawurlencode($texto);

        return view('ordens.success', compact('ordem', 'url', 'whatsappUrl'));
    }
}

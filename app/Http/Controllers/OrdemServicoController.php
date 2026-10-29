<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdemServicoRequest;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\Status;
use App\Models\User;
use App\Services\MensagensWhatsApp;
use App\Services\SalvarOrdemServico;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrdemServicoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['cliente_id' => ['nullable', 'integer', 'exists:clientes,id'], 'q' => ['nullable', 'string', 'max:255'], 'status_id' => ['nullable', 'integer', 'exists:statuses,id'], 'prazo' => ['nullable', Rule::in(['abertas', 'hoje', 'proximas', 'atrasadas'])], 'pagamento' => ['nullable', Rule::in(['pendente', 'quitado'])]]);
        $clienteFiltro = isset($filtros['cliente_id']) ? Cliente::findOrFail($filtros['cliente_id']) : null;
        $ordens = OrdemServico::with('status')->withCount('itens')->when($filtros['q'] ?? null, fn ($query, $q) => $query->where(function ($query) use ($q) {
            $query->where('nome', 'like', '%'.$q.'%')->orWhereHas('cliente', fn ($cliente) => $cliente->where('nome', 'like', '%'.$q.'%'))->orWhere('descricao', 'like', '%'.$q.'%')->orWhereHas('itens', fn ($itens) => $itens->where('descricao', 'like', '%'.$q.'%'))->orWhere('id', ltrim($q, '#0'));
        }))
            ->when($clienteFiltro, fn ($query, $cliente) => $query->where('cliente_id', $cliente->id))
            ->when($filtros['status_id'] ?? null, fn ($query, $id) => $query->where('status_id', $id))->prazo($filtros['prazo'] ?? null)
            ->when($filtros['pagamento'] ?? null, function ($query, $pagamento) {
                $operador = $pagamento === 'quitado' ? '>=' : '<';
                $query->whereRaw('round((select coalesce(sum(valor), 0) from pagamentos where pagamentos.ordem_servico_id = ordens_servico.id and pagamentos.estornado_em is null), 2) '.$operador.' ordens_servico.valor_total_final');
            })
            ->when($filtros['prazo'] ?? null, fn ($query) => $query->orderBy('data_entrega'))->latest()->paginate(15)->withQueryString();

        return view('ordens.index', ['ordens' => $ordens, 'statuses' => Status::orderBy('posicao')->get(), 'clienteFiltro' => $clienteFiltro]);
    }

    public function create(Request $request): View
    {
        $dados = $request->validate(['cliente_id' => ['nullable', 'integer', 'exists:clientes,id'], 'repetir' => ['nullable', 'integer', 'exists:ordens_servico,id']]);
        $origem = isset($dados['repetir']) ? OrdemServico::with('itens')->findOrFail($dados['repetir']) : null;
        if ($origem) {
            $origem->data_aprovacao = null;
            $origem->data_entrega = null;
            $origem->status_id = Status::where('slug', 'orcamento')->value('id') ?? Status::orderBy('posicao')->value('id');
        }

        return $this->formulario(origem: $origem);
    }

    private function formulario(?OrdemServico $ordem = null, ?OrdemServico $origem = null): View
    {
        $ordem?->load('itens');

        return view('ordens.create', [
            'ordem' => $ordem, 'origem' => $origem, 'empresa' => Empresa::atual(),
            'clientes' => Cliente::orderBy('nome')->get(['id', 'nome', 'telefone', 'documento', 'endereco', 'numero_endereco', 'cidade', 'bairro', 'uf', 'cep']),
            'statuses' => Status::orderBy('posicao')->orderBy('nome')->get(),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
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
            $ordem = $servico->salvar($request->safe()->except(['acao']), $ordem);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['documento' => 'Não foi possível salvar a OS. Tente novamente.']);
        }

        $mensagem = 'Ordem de serviço salva com sucesso.';
        $rota = route('ordens.success', $ordem->token);

        if ($request->input('acao') === 'salvar_docx') {
            $rota = route('ordens.success', ['token' => $ordem->token, 'baixar' => 1]);
            $mensagem = 'Ordem de serviço salva. O download do DOCX começou.';
        }

        if ($request->input('acao') === 'salvar_pdf') {
            $rota = route('ordens.success', ['token' => $ordem->token, 'baixar' => 'pdf']);
        }
        if ($request->input('acao') === 'salvar_whatsapp') {
            $rota = route('ordens.success', ['token' => $ordem->token, 'whatsapp' => 1]);
            $mensagem = 'OS salva. Confira a mensagem abaixo e abra o WhatsApp para enviar.';
        }

        return redirect()->to($rota)->with('success', $mensagem);
    }

    public function destroy(OrdemServico $ordem): RedirectResponse
    {
        $ordem->delete();

        return redirect()->route('ordens.index')->with('success', 'Ordem de serviço excluída.');
    }

    public function atualizarStatus(Request $request, OrdemServico $ordem, SalvarOrdemServico $servico): RedirectResponse|JsonResponse
    {
        $dados = $request->validate(['status_id' => ['required', 'integer', 'exists:statuses,id']]);
        try {
            $servico->alterarStatus($ordem, Status::findOrFail($dados['status_id']));
        } catch (Throwable $erro) {
            report($erro);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Não foi possível atualizar o status. Tente novamente.'], 500);
            }

            return back()->withErrors(['status_id' => 'Não foi possível atualizar o status. Tente novamente.']);
        }

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Status da OS atualizado.');

            return response()->json(['message' => 'Status da OS atualizado.']);
        }

        return back()->with('success', 'Status da OS atualizado.');
    }

    public function success(string $token, MensagensWhatsApp $mensagens): View
    {
        $ordem = OrdemServico::with('status', 'cliente', 'itens', 'pagamentos.usuario')->where('token', $token)->firstOrFail();

        return view('ordens.success', $this->dadosDetalhes($ordem, $mensagens));
    }

    public function acao(OrdemServico $ordem, string $acao, MensagensWhatsApp $mensagens): View
    {
        Gate::authorize(['status' => 'ordens.status', 'whatsapp' => 'ordens.visualizar', 'pagamentos' => 'pagamentos.visualizar'][$acao]);

        return view('ordens.acao', [...$this->dadosDetalhes($ordem, $mensagens), 'acao' => $acao]);
    }

    private function dadosDetalhes(OrdemServico $ordem, MensagensWhatsApp $mensagens): array
    {
        $ordem->loadMissing('status', 'cliente', 'itens', 'pagamentos.usuario');
        $url = route('documentos.show', ['token' => $ordem->token, 'formato' => 'docx']);
        $pdfUrl = route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']);
        $modelosWhatsApp = $mensagens->modelos($ordem);
        $whatsappUrl = $mensagens->url((string) $ordem->telefone, $modelosWhatsApp['pedido']);
        $telefoneWhatsApp = $mensagens->telefone((string) $ordem->telefone);
        $statuses = Status::orderBy('posicao')->get();

        return compact('ordem', 'url', 'pdfUrl', 'whatsappUrl', 'modelosWhatsApp', 'telefoneWhatsApp', 'statuses');
    }
}

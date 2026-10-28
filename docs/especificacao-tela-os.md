# Especificação técnica e visual — Cadastro/Edição de OS

## 1. Objetivo

Substituir o formulário linear por uma tela web de dashboard, rápida de operar e responsiva, com itens 1:N e resumo financeiro em tempo real. A folha impressa não é reproduzida na web: ela vive exclusivamente no DOCX exportado.

## 2. Escopo

- Tela de criação e edição de OS em cards.
- Catálogo de produtos (`/produtos`).
- Repetidor de itens com dois tipos de cobrança: unitário e m².
- ViaCEP, máscaras, validação inline e feedback de foco.
- Resumo financeiro sticky e barra de ações.
- DOCX fiel à folha física (A4 retrato, cabeçalho, tabela zebrada, assinatura e termo).
- Migração de OS antigas para um item (backfill). `migrate:fresh --seed` é aceitável neste projeto.

## 3. Layout

Desktop (≥ 1200 px): duas colunas — formulário (8 colunas) e resumo sticky (4 colunas).

```
┌──────────────────────────────────────────────────────────────┬─────────────────┐
│ Identificação da OS & Cliente                                │ RESUMO FINANCEIRO│
│ [Nº OS] [Data] [Status] [Cliente] [Contato]                  │ Itens, Qtd, Área │
├──────────────────────────────────────────────────────────────┤ Subtotal         │
│ Endereço de execução / entrega                               │ Desconto         │
│ [CEP ⟳] [Logradouro] [Número] [Complemento] [Bairro]...      │ VALOR TOTAL      │
├──────────────────────────────────────────────────────────────┤ (sticky)         │
│ Itens & Produtos                                             │                  │
│ ┌ Item 1 · (Unitário | m²) · duplicar/excluir ─────────────┐ │                  │
│ │ Produto [autocomplete]  Quantidade  Subtotal             │ │                  │
│ │ campos condicionais: valor unitário OU largura/altura/   │ │                  │
│ │ área/valor m²                                             │ │                  │
│ └───────────────────────────────────────────────────────────┘ │                  │
│ + Adicionar produto                                          │                  │
├──────────────────────────────────────────────────────────────┤                  │
│ Observações, condições & prazos                              │                  │
└──────────────────────────────────────────────────────────────┴─────────────────┘
[Cancelar]                              [Salvar e baixar Word] [Salvar OS]
```

Mobile (< 640 px): cards empilhados; resumo e barra de ações fixos no rodapé; botões em largura total; o resumo mostra o total e pode ser consultado por scroll.

## 4. Cards e campos

### 4.1 Identificação da OS & Cliente

- Número da OS e data: somente leitura.
- Status: select obrigatório.
- Cliente: select obrigatório; ao trocar, busca `/clientes/{id}/dados` e atualiza telefone/documento.
- Contato: chips com telefone e CPF/CNPJ.
- Links: cadastrar novo cliente / editar cadastro.

### 4.2 Endereço de execução / entrega

- CEP com máscara `00000-000` e gatilho do ViaCEP.
- Logradouro, número, bairro, cidade e UF.
- Switch “Usar o endereço do cliente como base ao trocar o cliente”.
- O número é preservado no preenchimento automático; o foco vai para ele após o CEP.

Fluxo ViaCEP:

1. Com 8 dígitos (ou no blur), consulta `https://viacep.com.br/ws/{cep}/json/` com `AbortController` e timeout de 8 s.
2. Spinner dentro do input e `aria-busy="true"`.
3. Sucesso: preenche logradouro, bairro, cidade e UF; foca o número; anuncia “Endereço preenchido pelo CEP”.
4. `{erro: true}`, HTTP inválido, timeout ou rede: mensagem amigável, campos continuam editáveis e o CEP pode ser corrigido.
5. Consultas repetidas do mesmo CEP são evitadas na sessão.

### 4.3 Itens & Produtos (1:N)

Cada item é um card com:

- Produto/descrição com autocomplete nativo (`datalist`) do catálogo ativo. Selecionar um produto preenche tipo e valor; o texto pode ser livre.
- Tipo de cobrança em segmented control: **Unitário** ou **m²**. O tipo do produto é aplicado, mas pode ser alterado manualmente.
- Quantidade (até 4 casas).
- Subtotal calculado e somente leitura.
- Campos condicionais:
  - Unitário: valor unitário.
  - m²: largura, altura, área total calculada e valor do m².
- Detalhes adicionais colapsáveis: código, referência, formato, vias e cores.
- Ações: duplicar e excluir. A exclusão do último item é bloqueada; a remoção dos demais é instantânea com toast “Desfazer” (6 s).
- “+ Adicionar produto” no cabeçalho do card e um botão tracejado no fim da lista.

A indicação de unidade nas telas, documentos e mensagens é derivada do tipo de cobrança do item: `un` para unitário e `m²` para metro quadrado. Não há campo de unidade separado no produto ou na OS.

Regras de cálculo:

- Unitário: `subtotal = round(quantidade × valor_unitario, 2)`.
- m²: `area = round(largura × altura, 4)`; `subtotal = round(area × quantidade × valor_m2, 2)`.
- Totais: `valor_total` = soma dos subtotais; `valor_produtos = valor_total`; `valor_total_final = valor_produtos − desconto`.
- O servidor recalcula tudo; totais enviados pelo cliente são ignorados.

### 4.4 Observações, condições & prazos

- Responsável, data de aprovação, data de entrega, condições de pagamento.
- Serviço realizado e observações gerais.
- Data de entrega não pode ser anterior à aprovação.

### 4.5 Resumo financeiro

- Itens, quantidade de unitários, quantidade de m², área total (m²), subtotal, desconto e total.
- Desconto único, aplicado sobre o subtotal.
- Valor total destacado.
- Atualização a cada `input`, com `aria-live="polite"`.
- Totais negativos bloqueiam o envio e exibem mensagem.

### 4.6 Barra de ações

- **Salvar OS**: submit primário (`acao=salvar`).
- **Salvar e baixar Word**: submit secundário (`acao=salvar_docx`); após salvar, redireciona para a visualização com `?baixar=1` e dispara o download.
- **Cancelar**: ghost; confirma descarte se houver alterações.
- `beforeunload` alerta sobre alterações não salvas.

## 5. Máscaras e validação

- CEP: `00000-000`.
- Telefone: `(00) 0000-0000` / `(00) 00000-0000`.
- CPF/CNPJ: `000.000.000-00` / `00.000.000/0000-00`.
- Moeda: `R$ 1.234,56`, com `inputmode="decimal"`.
- Decimais: vírgula ou ponto, até 4 casas; normalização no blur.
- Validação inline: borda vermelha para inválido e verde suave para válido após interação; mensagens do servidor por campo.
- O servidor normaliza valores com vírgula e valida faixas, decimais e campos condicionais.

## 6. Acessibilidade e responsividade

- Labels associados, `aria-invalid`, `aria-live` no CEP e no resumo.
- Foco visível em dourado; navegação por teclado no segmented control e no autocomplete.
- Alvos de toque ≥ 44 px.
- `prefers-reduced-motion` desativa animações.
- Breakpoints: 1200, 1099, 900, 640 px.
- No mobile, o resumo e a barra de ações ficam fixos; o conteúdo recebe `padding-bottom` para não ficar oculto.

## 7. Modelo de dados

### produtos

| Campo | Tipo |
|---|---|
| id | bigint |
| nome | string |
| tipo | string (`unitario`/`m2`) |
| valor_unitario | decimal(15,2) |
| valor_m2 | decimal(15,2) |
| ativo | boolean |
| timestamps | |

### ordem_servico_itens

| Campo | Tipo |
|---|---|
| id | bigint |
| ordem_servico_id | FK `ordens_servico` (cascade) |
| produto_id | FK `produtos` nullable (nullOnDelete) |
| tipo | string (`unitario`/`m2`) |
| descricao | string |
| codigo, referencia, formato, vias, cores | string nullable |
| quantidade | decimal(15,4) |
| largura, altura, area | decimal(15,4) nullable |
| valor_unitario | decimal(15,2) |
| valor_m2 | decimal(15,2) |
| subtotal | decimal(15,2) |
| posicao | unsigned int |
| timestamps | |

### ordens_servico

- Cabeçalho: cliente, status, nome, documento, telefone, endereço, `numero_endereco`, bairro, cidade, UF, CEP.
- Totais: `total_m2`, `quantidade`, `valor_total`, `valor_produtos`, `desconto`, `valor_total_final`.
- Observações, serviço realizado, responsável, condições, datas, situação e token; sem caminho de arquivo persistido.
- `descricao` é o resumo (primeiro item) usado nas listagens.

A migração `backfill_ordem_servico_itens` converte OS antigas em um item: m² quando largura e altura existem; unitário caso contrário.

## 8. Backend

Rotas principais:

```php
Route::resource('produtos', ProdutoController::class)->except('show')->parameters(['produtos' => 'produto']);
Route::resource('ordens', OrdemServicoController::class)->except('show')->parameters(['ordens' => 'ordem']);
Route::get('/ordens/{token}/sucesso', [OrdemServicoController::class, 'success'])->whereUuid('token')->name('ordens.success');
```

- `StoreOrdemServicoRequest`: valida cabeçalho, itens (1..50), tipo, campos condicionais e desconto; normaliza moeda/medidas; mescla a identidade do cliente.
- `CalcularOrdemServico`: calcula itens e totais; rejeita totais negativos.
- `SalvarOrdemServico`: transação; salva a OS e recria os itens; não gera documentos ao salvar.
- `ProdutoController`: CRUD; bloqueia exclusão de produto usado em item.
- `GerarDocumentoOrdemServico`: DOCX via PHPWord sob demanda pela rota, sem cópia persistida. O arquivo temporário de trabalho é removido ao concluir a geração.

## 9. PDF e Word — documentos padronizados

- O layout do PDF é a referência para o Word; ambos usam A4 retrato e a mesma sequência de blocos.
- O logo original `public/img/logo-gold-press.png` aparece no cabeçalho dos dois formatos. O Docker inclui GD para incorporar esse PNG no PDF.
- Cabeçalho: logo e dados da empresa à esquerda; título, número e status da OS à direita.
- Cliente: nome, documento, telefone e endereço à esquerda; emissão, entrega e aprovação à direita. Responsável da gráfica abaixo.
- Itens: Produto / serviço | Qtd. | Medidas | Preço | Subtotal. Código, referência, formato, cores e vias aparecem junto à descrição. Cabeçalho repetido nas páginas seguintes.
- Resumo financeiro à direita: subtotal, desconto, total, valor pago e saldo restante.
- Condições de pagamento, observações e serviço realizado abaixo, quando preenchidos.
- Rodapé com identificação da OS, data de geração e paginação. Documentos extensos podem ocupar mais de uma página.
- `DadosDocumentoOrdemServico` concentra os campos e a formatação compartilhados pelos dois geradores.
- Escaping HTML/XML habilitado; nenhuma cópia de PDF ou Word é mantida no storage.

## 10. Critérios de aceite

- Criar OS com um item unitário e um item m²; subtotais e total corretos; DOCX com duas linhas e formatação da folha em uma página.
- Alternar o tipo do item exibe/oculta os campos corretos e remove do envio os campos inativos.
- Excluir o último item é bloqueado; excluir outros permite desfazer.
- CEP válido preenche o endereço e foca o número; CEP inválido libera edição manual.
- Máscaras de CEP, telefone, CPF/CNPJ e moeda funcionam.
- Resumo atualiza em tempo real e bloqueia total negativo.
- “Salvar e baixar Word” salva e inicia o download.
- Edição preserva o token; o próximo acesso gera PDF ou DOCX com os dados atuais, sem armazenar arquivos.
- Layout funcional em 1440, 1024 e 390 px; navegação por teclado e foco visível.
- Suíte de testes passando, incluindo cálculo, validação condicional, catálogo, backfill e DOCX.

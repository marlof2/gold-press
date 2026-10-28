# Gold Press — ordens de serviço

Laravel 13, Blade, JavaScript vanilla, SQLite e PhpWord. PHP 8.4 + Apache executados em Docker; não precisa instalar PHP, Composer ou Node no computador.

## Executar

```sh
cp .env.example .env
docker compose up -d --build
```

A URL configurada para a VPS é http://144.91.76.180:9090. O serviço permanece restrito ao endereço local do servidor por padrão; o acesso externo depende de um proxy ou de uma alteração explícita da publicação da porta. O primeiro início cria a chave da aplicação, o SQLite, as tabelas e o link `public/storage`. A chave fica persistida em `storage/app.key`, fora da pasta pública.

Não há usuário, senha ou dado da empresa no `.env`. O primeiro administrador é criado pelo seed (`marlosilva.f2@gmail.com`, perfil Administrador) com a senha padrão `12345678`. Depois do primeiro acesso, altere a senha em **Minha conta**. Os dados da gráfica (nome, CNPJ, endereço, telefone) são editados em **Empresa** no painel. Para mudar a porta, configure `APP_PORT` e ajuste `APP_URL` para a mesma porta. Após alterar variáveis, execute `docker compose up -d`.

## Desenvolvimento e hot reload

O `Dockerfile` copia o código para dentro da imagem. Sem bind mount, alterações só aparecem depois de `docker compose up -d --build`. Para o dia a dia, o `compose.override.yaml` monta `app`, `resources`, `routes`, `config`, `database`, `public`, `lang`, `tests`, `bootstrap/app.php`, `bootstrap/providers.php` e `.env` no container:

```sh
# uma vez, para recriar o container com os mounts
docker compose up -d
```

Depois disso, alterações em PHP, Blade, CSS e JS aparecem ao recarregar a página, sem rebuild. Não é HMR (o projeto usa CSS/JS estáticos, sem Vite); é código ao vivo. Os assets usam `filemtime` na URL, então o navegador busca a versão nova; se ainda cachear, use Ctrl+F5.

Rebuild apenas quando mudar `Dockerfile`, `compose.yaml`, o entrypoint ou dependências do Composer:

```sh
docker compose up -d --build
```

Em produção, ignore o override:

```sh
docker compose -f compose.yaml up -d --build
```

O override define `APP_ENV=local` e `APP_DEBUG=true`; não use em produção.

## Cálculo e itens

Cada OS tem 1:N itens (`ordem_servico_itens`), com dois tipos de cobrança:

- **Unitário:** `subtotal = quantidade × valor_unitario`.
- **Metro quadrado (m²):** `area = largura × altura` (4 casas); `subtotal = area × quantidade × valor_m2`.

Totais da OS:

- `total_m2` = soma das áreas dos itens em m².
- `quantidade` = soma interna das quantidades; o resumo exibe separadamente a quantidade unitária e a quantidade em m².
- `valor_total` = soma dos subtotais dos itens.
- `valor_produtos = valor_total`.
- `valor_total_final = valor_produtos − desconto`.

Cada etapa monetária é arredondada para centavos. O único ajuste financeiro é o desconto, que não pode ser maior que o subtotal. Totais negativos são rejeitados. O formulário recalcula a cada `input`; o servidor recalcula tudo a partir dos itens e ignora totais enviados pelo navegador. Quantidade e medidas aceitam até 4 casas decimais.

O catálogo em `/produtos` define tipo de cobrança e valor padrão. Ao selecionar um produto no autocomplete da OS, o tipo e o valor são preenchidos; o operador pode ajustar manualmente. A indicação de unidade é derivada do tipo de cobrança: `un` para unitário e `m²` para metro quadrado. Produtos inativos não aparecem no autocomplete, mas continuam nos itens existentes.

## Arquivos principais

- `database/migrations/2026_10_27_113002_create_produtos_table.php`: catálogo de produtos.
- `database/migrations/2026_10_27_113003_create_ordem_servico_itens_table.php`: itens 1:N da OS.
- `database/migrations/2026_10_27_113005_backfill_ordem_servico_itens.php`: converte OS antigas em um item.
- `app/Models/Produto.php` e `app/Models/OrdemServicoItem.php`: catálogo e itens.
- `app/Models/OrdemServico.php`: cabeçalho, totais e relação `itens`.
- `app/Http/Requests/StoreOrdemServicoRequest.php`: validação condicional por tipo de item.
- `app/Services/CalcularOrdemServico.php`: cálculo de itens e totais no servidor.
- `app/Services/SalvarOrdemServico.php`: transação e sincronização dos itens, sem gerar documentos ao salvar.
- `app/Services/GerarDocumentoOrdemServico.php`: DOCX fiel à folha física (A4 retrato, cabeçalho, tabela zebrada, totais e assinatura).
- `app/Http/Controllers/OrdemServicoController.php` e `ProdutoController.php`: telas, transação, DOCX, WhatsApp e catálogo.
- `resources/views/ordens/create.blade.php` e `ordens/partials/item.blade.php`: formulário em cards e repetidor de itens.
- `resources/views/ordens/success.blade.php`: visualização moderna da OS.
- `resources/views/produtos/`: CRUD do catálogo.
- `public/js/masks.js`: máscaras de CEP, telefone, CPF/CNPJ, moeda e decimais.
- `public/js/os-form.js`: ViaCEP, repetidor, cálculo reativo, resumo, toasts e validação inline.
- `public/css/os-form.css`: layout dos cards, resumo sticky e barra de ações.

O botão do WhatsApp abre `https://wa.me/55DDDNUMERO?text=...` com mensagem codificada por `rawurlencode`, número da OS, valor final e `Storage::disk('public')->url(...)`. Aceita telefone brasileiro com DDD ou prefixo 55. O usuário confirma o envio no WhatsApp.

## Link público e publicação

Na VPS configurada, os links usam `http://144.91.76.180:9090/storage/ordens/...docx`. **Um cliente em outro aparelho não consegue acessar o seu localhost.** Para compartilhar, hospede a aplicação em um domínio acessível (HTTPS) ou publique por um túnel e configure `APP_URL` com esse endereço. O Compose mantém a porta em `127.0.0.1` por padrão. Para acesso externo, configure o encaminhamento no proxy do painel de deploy ou publique a porta explicitamente. Os arquivos são públicos a quem possuir o link, conforme solicitado; usam nomes UUID.

Todas as páginas administrativas exigem login. Os documentos PDF e DOCX são acessíveis sem login pelo link com token UUID da OS, para permitir o envio ao cliente. Ao publicar com HTTPS, defina `APP_ENV=production` e `SESSION_SECURE_COOKIE=true`. Não exponha o diretório raiz da aplicação; o Apache serve somente `public/`.

## Dados persistentes e comandos

Volumes `sqlite_data` e `app_storage` guardam o banco, a chave e sessões; documentos de OS não são mantidos em disco. `docker compose down` preserva os dados. `docker compose down -v` apaga esses volumes.

```sh
docker compose exec app php artisan test
docker compose logs -f app
docker compose exec app php artisan migrate:status
docker compose down
```

Os testes usam SQLite em memória e disco público temporário. Cobrem cálculo de itens unitários e m², validação condicional, rollback, DOCX legível/escape XML, catálogo, backfill de OS legadas, página de sucesso e URL WhatsApp. Para backup, pare o serviço e copie os dois volumes; preserve a chave junto aos dados.

Para recriar o banco do zero neste projeto (aprovado): `docker compose exec app php artisan migrate:fresh --seed`. A migração de backfill também converte OS antigas em um item quando não há `migrate:fresh`.

Referências: [Laravel Storage](https://laravel.com/docs/13.x/filesystem), [PHPWord](https://phpoffice.github.io/PHPWord/usage/introduction.html).

## Gestão, login e navegação

- Acesso em `/login`, painel em `/`, listagem em `/ordens` e criação em `/ordens/create`.
- Pesquisa de OS por nome atual ou histórico do cliente, produto e número; filtro por status e paginação.
- O atalho **Criar OS** aparece apenas na Visão geral e na listagem de ordens; sidebar e top bar não repetem o atalho.
- A criação/edição da OS usa cards modernos: identificação e cliente, endereço de execução/entrega com ViaCEP, itens em repetidor, observações/prazos e resumo financeiro sticky. A tela web não replica a folha impressa.
- O PDF é gerado a cada acesso a `/documentos/{token}/pdf`, com os dados e pagamentos atuais, e enviado diretamente na resposta (`inline` ou download). Não é armazenado em `storage`; a resposta usa `Cache-Control: private, no-store`. O layout inclui detalhes dos itens, resumo de total/pago/saldo, cabeçalho da tabela repetido e numeração de páginas.
- O Word segue a disposição do PDF: A4 retrato, mesmo logo, cabeçalho com empresa e identificação da OS, cliente e prazos em duas colunas, tabela de cinco colunas, financeiro à direita e notas abaixo. Ambos usam os mesmos dados formatados e têm paginação.
- Máscaras automáticas para CEP, telefone/WhatsApp, CPF/CNPJ e valores em R$; validação inline e feedback de foco.
- Clientes em `/clientes`: cadastro, pesquisa, visualização, edição, exclusão e histórico de OS.
- Produtos em `/produtos`: catálogo com tipo unitário/m², valor padrão e status ativo/inativo.
- Usuários em `/usuarios`: cadastro, pesquisa, edição, exclusão, perfil e redefinição de senha. Não é possível excluir a própria conta nem o último usuário.
- Empresa em `/empresa`: nome, razão social, CNPJ, endereço, telefone, e-mail e site usados no cabeçalho do DOCX.
- Status em `/statuses`: nome, cor, posição e opção de finalizar a ordem. Status finalizados saem das contagens de ordens abertas e atrasadas.
- Edição e exclusão de OS pela página de detalhes. PDF e Word são gerados apenas ao acessar `/documentos/{token}/{formato}`, sempre com os dados e pagamentos atuais, sem manter cópias em `storage`. O Word usa um arquivo temporário de trabalho, removido ao finalizar a geração, inclusive em falhas.
- Clientes, status e produtos vinculados a OS não podem ser excluídos. Produtos podem ser desativados.
- O cadastro do cliente preenche a identificação e pode preencher o endereço de execução; o operador pode ajustar o endereço e o número. Alterar um cliente não reescreve automaticamente as ordens antigas.
- Layout para celular com cards empilhados, resumo e barra de ações fixos, menu lateral recolhível e atalhos inferiores.

Para remover cópias antigas de documentos em `storage/app/public/ordens`, execute `php artisan ordens:limpar-documentos --dry-run` para conferir a quantidade e depois `php artisan ordens:limpar-documentos`. O comando preserva outros tipos de arquivo e arquivos fora dessa pasta. Observações muito longas ou dezenas de itens podem exigir mais de uma página.

O seed (`php artisan db:seed`, executado automaticamente na subida do container) cria os perfis **Administrador** e **Operador**, o registro inicial da empresa, cinco produtos de exemplo e o usuário `marlosilva.f2@gmail.com` com perfil Administrador e senha padrão `12345678`. Troque a senha após o primeiro acesso. O seed é idempotente: não recria nem redefine registros existentes.

O login tem limite de tentativas, a sessão é renovada ao entrar, o logout invalida a sessão e as mutações usam proteção CSRF. Não há cadastro público de usuários e não existe a opção "Manter conectado": a sessão expira após 30 dias de inatividade (`SESSION_LIFETIME=43200`, em minutos), e o prazo é renovado a cada acesso. O driver de sessão é `database`, e cada login grava um token único em `users.session_token`. Se a mesma conta entrar em outro dispositivo, a sessão anterior é encerrada no próximo acesso.

A suíte cobre também autenticação, sessão única, tentativas de login, alteração de senha, CRUD de usuários/perfis, dados da empresa, catálogo de produtos, itens unitários/m², cálculo autoritativo, filtros, paginação, proteção das rotas, backfill de OS legadas e preservação do documento em falhas de edição.

Para acessar pelo celular na mesma rede, configure `APP_BIND_ADDRESS=0.0.0.0` e `APP_URL=http://IP-DO-COMPUTADOR:9090`, recrie o serviço com `docker compose up -d` e abra esse endereço no celular. Em Docker/WSL no Windows, pode ser necessário liberar a porta no firewall e configurar o encaminhamento do WSL. Para clientes fora da rede, use o domínio HTTPS ou túnel público.

## VPS 144.91.76.180

Configure estas variáveis no `.env` ou no painel de deploy:

```dotenv
APP_ENV=production
APP_URL=http://144.91.76.180:9090
APP_PORT=9090
APP_BIND_ADDRESS=127.0.0.1
SESSION_SECURE_COOKIE=false
```

Mantenha `SESSION_DRIVER=database` para o controle de sessão única. Após atualizar o código ou as variáveis, refaça o deploy no painel ou execute `docker compose -f compose.yaml up -d --build` (sem o override de desenvolvimento). Para o endereço público `http://144.91.76.180:9090/login` funcionar, ainda é necessário configurar o acesso externo no proxy ou na publicação da porta; o padrão atual aceita apenas conexões locais no servidor.

Variáveis já definidas no painel ou no `.env` prevalecem sobre os valores padrão do Compose; substitua configurações antigas de `APP_URL`, `APP_PORT` e `APP_BIND_ADDRESS`. O servidor web escuta na porta 80 dentro do container; 9090 é a porta publicada da VPS.

Para executar somente no computador local, use `APP_URL=http://localhost:9090`, `APP_BIND_ADDRESS=127.0.0.1` e `APP_ENV=local`.

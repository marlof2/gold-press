# Gold Press — ordens de serviço

Laravel 13, Blade, JavaScript vanilla, SQLite e PhpWord. PHP 8.4 + Apache executados em Docker; não precisa instalar PHP, Composer ou Node no computador.

## Executar

```sh
cp .env.example .env
# Edite .env e configure ADMIN_EMAIL e ADMIN_PASSWORD antes de iniciar.
docker compose up -d --build
```

Abra http://localhost:9090. O primeiro início cria a chave da aplicação, o SQLite, as tabelas e o link `public/storage`. A chave fica persistida em `storage/app.key`, fora da pasta pública.

Personalize `GRAFICA_NOME`, `GRAFICA_ENDERECO`, `GRAFICA_TELEFONE` e `APP_URL` no `.env`. Para mudar a porta, configure `APP_PORT` e ajuste `APP_URL` para a mesma porta. Após alterar variáveis, execute `docker compose up -d`.

## Cálculo

- `total_m2 = altura × largura`, em metros, arredondado para 4 casas.
- `valor_total_m2 = total_m2 × valor_produto_m2`, valor por unidade em reais.
- `valor_total = valor_total_m2 × quantidade`.
- `valor_produtos = valor_total + arredondamento + acerto_valor`.
- `valor_total_final = valor_produtos + valor_servicos + deslocamento − desconto`.

Cada etapa monetária é arredondada para centavos. Arredondamento e acerto são ajustes em reais que aceitam sinal negativo. Os demais valores não aceitam negativos; totais negativos são rejeitados. O formulário recalcula a cada `input`; o servidor recalcula tudo e ignora totais enviados pelo navegador. Quantidade aceita até 4 casas decimais.

## Arquivos principais

- `database/migrations/2026_10_26_000001_create_ordens_servico_table.php`: todos os campos da OS.
- `app/Models/OrdemServico.php`: atributos e conversões decimais/datas.
- `app/Http/Requests/StoreOrdemServicoRequest.php`: validação.
- `app/Services/CalcularOrdemServico.php`: cálculo no servidor.
- `app/Services/GerarDocumentoOrdemServico.php`: PhpWord, cabeçalho, tabelas e gravação em `Storage::disk('public')`.
- `app/Http/Controllers/OrdemServicoController.php`: transação, geração, limpeza em falhas, URL pública e WhatsApp.
- `resources/views/ordens/create.blade.php`: formulário.
- `resources/views/ordens/success.blade.php`: download e botão WhatsApp.
- `public/js/ordem.js`: cálculo em tempo real.

O botão abre `https://wa.me/55DDDNUMERO?text=...` com mensagem codificada por `rawurlencode`, número da OS, valor final e `Storage::disk('public')->url(...)`. Aceita telefone brasileiro com DDD ou prefixo 55. O usuário confirma o envio no WhatsApp.

## Link público e publicação

Em desenvolvimento, o link usa `http://localhost:9090/storage/ordens/{uuid}.docx`. **Um cliente em outro aparelho não consegue acessar o seu localhost.** Para compartilhar, hospede a aplicação em um domínio acessível (HTTPS) ou publique por um túnel e configure `APP_URL` com esse endereço. O Compose limita a porta a `127.0.0.1`; um proxy/túnel local pode encaminhar o acesso. Os arquivos são públicos a quem possuir o link, conforme solicitado; usam nomes UUID.

Todas as páginas administrativas exigem login. O único documento acessível sem login é o DOCX público com nome UUID, para permitir o envio ao cliente. Ao publicar com HTTPS, defina `APP_ENV=production` e `SESSION_SECURE_COOKIE=true`. Não exponha o diretório raiz da aplicação; o Apache serve somente `public/`.

## Dados persistentes e comandos

Volumes `sqlite_data` e `app_storage` guardam o banco, a chave, sessões e DOCX. `docker compose down` preserva os dados. `docker compose down -v` apaga esses volumes.

```sh
docker compose exec app php artisan test
docker compose logs -f app
docker compose exec app php artisan migrate:status
docker compose down
```

Os testes usam SQLite em memória e disco público temporário. Cobrem cálculo autoritativo, validação, rollback, DOCX legível/escape XML, página de sucesso e URL WhatsApp. Para backup, pare o serviço e copie os dois volumes; preserve a chave junto aos dados.

Referências: [Laravel Storage](https://laravel.com/docs/13.x/filesystem), [PHPWord](https://phpoffice.github.io/PHPWord/usage/introduction.html).

## Gestão, login e navegação

- Acesso em `/login`, painel em `/`, listagem em `/ordens` e criação em `/ordens/create`.
- Pesquisa de OS por nome atual ou histórico do cliente, produto e número; filtro por status e paginação.
- Clientes em `/clientes`: cadastro, pesquisa, visualização, edição, exclusão e histórico de OS.
- Status em `/statuses`: nome, cor, posição e opção de finalizar a ordem. Status finalizados saem das contagens de ordens abertas e atrasadas.
- Edição e exclusão de OS pela página de detalhes; editar gera um novo DOCX. O arquivo anterior é removido após a confirmação da gravação. Se a geração falhar, o documento anterior e os dados da OS são preservados.
- Clientes e status vinculados a OS não podem ser excluídos. A migração importa os clientes/status das OS antigas e preserva seus documentos e valores.
- O cadastro do cliente preenche a OS no servidor. Alterar um cliente não reescreve automaticamente as ordens antigas.
- Layout para celular com menu lateral recolhível, atalhos inferiores e listagens em blocos.

No primeiro início, `app:bootstrap-admin` cria o usuário definido em `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD`. A senha deve ter pelo menos 12 caracteres, letras e números. Configure os valores no `.env`; não existe senha padrão no código. O comando não altera usuários já existentes.

Altere a senha após o primeiro acesso em **Minha conta**. O login tem limite de tentativas, a sessão é renovada ao entrar, o logout invalida a sessão e as mutações usam proteção CSRF. Não há cadastro público de usuários. Os usuários autenticados pertencem à mesma equipe da gráfica e acessam todos os registros.

A suíte cobre também autenticação, tentativas de login, alteração de senha, CRUD de clientes/status, filtros, paginação, proteção das rotas, migração de OS legadas e preservação do documento em falhas de edição.

Para acessar pelo celular na mesma rede, configure `APP_BIND_ADDRESS=0.0.0.0` e `APP_URL=http://IP-DO-COMPUTADOR:9090`, recrie o serviço com `docker compose up -d` e abra esse endereço no celular. Em Docker/WSL no Windows, pode ser necessário liberar a porta no firewall e configurar o encaminhamento do WSL. Para clientes fora da rede, use o domínio HTTPS ou túnel público.

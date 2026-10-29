(() => {
    const form = document.getElementById('ordem-form');
    if (!form) return;
    const cliente = document.getElementById('cliente_id');
    const whatsapp = document.querySelector('[data-whatsapp-cliente]');
    const editar = document.querySelector('[data-cliente-editar]');

    function atualizarContato() {
        const dados = JSON.parse(cliente?.selectedOptions[0]?.dataset.cliente || '{}');
        let telefone = String(dados.telefone || '').replace(/\D/g, '');
        if ([10, 11].includes(telefone.length)) telefone = `55${telefone}`;
        whatsapp.hidden = !/^55[1-9]\d{9,10}$/.test(telefone);
        if (!whatsapp.hidden) {
            const mensagem = `Olá, ${dados.nome}! Tudo bem? Aqui é da ${form.dataset.empresa}. Estamos entrando em contato sobre seu pedido. Como podemos ajudar?`;
            whatsapp.href = `https://wa.me/${telefone}?text=${encodeURIComponent(mensagem)}`;
        } else {
            whatsapp.removeAttribute('href');
        }
        editar.hidden = !cliente.value;
        if (cliente.value) editar.href = `${cliente.dataset.url}/${cliente.value}/edit`;
    }
    form.addEventListener('gp:cliente', atualizarContato);
    atualizarContato();

    const dialog = document.getElementById('cliente-rapido');
    const cadastro = document.getElementById('cliente-rapido-form');
    const titulo = document.getElementById('cliente-rapido-titulo');
    const descricao = cadastro.querySelector('[data-cliente-descricao]');
    const erros = cadastro.querySelector('[data-cliente-erros]');
    const carregando = cadastro.querySelector('[data-cliente-carregando]');
    const campos = cadastro.querySelector('[data-cliente-campos]');
    const botao = cadastro.querySelector('[type="submit"]');
    const textoBotao = cadastro.querySelector('[data-cliente-submit-text]');
    const urlCadastro = cadastro.action;
    let clienteEmEdicao = null;
    let requisicao;
    let salvando = false;

    function cancelarCep() {
        const cep = cadastro.elements.namedItem('cep');
        const valor = cep.value;
        cep.value = '';
        cep.dispatchEvent(new Event('input'));
        cep.value = valor;
    }
    function preparar(id = null) {
        if (salvando) return false;
        requisicao?.abort();
        requisicao = null;
        cancelarCep();
        cadastro.reset();
        clienteEmEdicao = id;
        cadastro.action = id ? `${cliente.dataset.url}/${id}` : urlCadastro;
        titulo.textContent = id ? 'Editar cliente' : 'Cadastrar cliente';
        descricao.textContent = id ? 'Atualize o cadastro sem sair desta ordem de serviço.' : 'O cliente será selecionado nesta OS ao salvar.';
        textoBotao.textContent = id ? 'Salvar cadastro' : 'Cadastrar e selecionar';
        erros.hidden = true;
        carregando.hidden = true;
        campos.disabled = false;
        botao.disabled = false;
        if (!dialog.open) dialog.showModal();
        return true;
    }
    function erroDaResposta(dados, padrao) {
        return dados.errors ? Object.values(dados.errors).flat().join(' ') : dados.message || padrao;
    }
    document.querySelector('[data-cliente-novo]')?.addEventListener('click', () => preparar());
    editar?.addEventListener('click', async (evento) => {
        evento.preventDefault();
        const id = cliente.value;
        if (!id || !preparar(id)) return;
        const atual = new AbortController();
        requisicao = atual;
        carregando.hidden = false;
        campos.disabled = true;
        botao.disabled = true;
        let carregado = false;
        try {
            const resposta = await fetch(editar.href, { signal: atual.signal, headers: { Accept: 'application/json' } });
            const dados = await resposta.json().catch(() => ({}));
            if (!resposta.ok || !dados.id) throw new Error(erroDaResposta(dados, 'Não foi possível carregar o cadastro. Tente novamente.'));
            if (atual.signal.aborted) return;
            ['nome', 'tipo_pessoa', 'documento', 'telefone', 'email', 'cep', 'endereco', 'numero_endereco', 'bairro', 'cidade', 'uf', 'observacoes'].forEach((nome) => {
                const campo = cadastro.elements.namedItem(nome);
                campo.value = dados[nome] ?? '';
                if (nome === 'telefone') campo.value = window.GPMasks.maskTelefone(campo.value);
                if (nome === 'documento') campo.value = window.GPMasks.maskCpfCnpj(campo.value);
                if (nome === 'cep') campo.value = window.GPMasks.maskCep(campo.value);
            });
            cadastro.querySelector('[data-tipo-pessoa]')?.dispatchEvent(new Event('change'));
            carregado = true;
        } catch (erro) {
            if (erro.name === 'AbortError') return;
            erros.textContent = erro.message;
            erros.hidden = false;
            return;
        } finally {
            if (requisicao === atual) {
                carregando.hidden = true;
                campos.disabled = !carregado;
                botao.disabled = !carregado;
            }
        }
        cadastro.elements.namedItem('nome').focus();
    });
    document.querySelectorAll('[data-cliente-fechar]').forEach((fechar) => fechar.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => {
        requisicao?.abort();
        requisicao = null;
        carregando.hidden = true;
        campos.disabled = false;
        botao.disabled = false;
        cancelarCep();
    });

    function enderecoIgual(dados) {
        return ['cep', 'endereco', 'numero_endereco', 'bairro', 'cidade', 'uf'].every((nome) => {
            let valor = String(form.elements.namedItem(nome)?.value ?? '').trim();
            let anterior = String(dados[nome] ?? '').trim();
            if (nome === 'cep') {
                valor = window.GPMasks.onlyDigits(valor);
                anterior = window.GPMasks.onlyDigits(anterior);
            }
            return valor === anterior;
        });
    }
    cadastro.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (salvando || botao.disabled) return;
        salvando = true;
        botao.disabled = true;
        erros.hidden = true;
        cancelarCep();
        const idEditado = clienteEmEdicao;
        const opcaoAnterior = [...cliente.options].find((opcao) => opcao.value === idEditado);
        const dadosAnteriores = JSON.parse(opcaoAnterior?.dataset.cliente || '{}');
        const dadosFormulario = new FormData(cadastro);
        if (idEditado) dadosFormulario.set('_method', 'PUT');
        try {
            const resposta = await fetch(cadastro.action, { method: 'POST', body: dadosFormulario, headers: { Accept: 'application/json' } });
            const dados = await resposta.json().catch(() => ({}));
            if (!resposta.ok || !dados.id) throw new Error(erroDaResposta(dados, 'Não foi possível salvar o cadastro. Verifique sua conexão e tente novamente.'));
            const preservarEndereco = Boolean(idEditado && !enderecoIgual(dadosAnteriores));
            let opcao = [...cliente.options].find((item) => item.value === String(dados.id));
            if (!opcao) {
                opcao = new Option('', dados.id);
                cliente.add(opcao);
            }
            opcao.textContent = `${dados.nome} · ${dados.telefone}`;
            opcao.dataset.cliente = JSON.stringify(dados);
            if ((!idEditado && dialog.open) || cliente.value === idEditado) {
                cliente.value = String(dados.id);
                cliente.dispatchEvent(new CustomEvent('change', { bubbles: true, detail: { preservarEndereco } }));
                form.dispatchEvent(new Event('input', { bubbles: true }));
            }
            cadastro.reset();
            dialog.close();
        } catch (erro) {
            erros.textContent = erro.message;
            erros.hidden = false;
        } finally {
            salvando = false;
            botao.disabled = false;
        }
    });
})();

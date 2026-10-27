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
    document.querySelector('[data-cliente-novo]')?.addEventListener('click', () => dialog.showModal());
    document.querySelectorAll('[data-cliente-fechar]').forEach((botao) => botao.addEventListener('click', () => dialog.close()));
    cadastro?.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const botao = cadastro.querySelector('[type="submit"]');
        if (botao.disabled) return;
        botao.disabled = true;
        const erros = cadastro.querySelector('[data-cliente-erros]');
        erros.hidden = true;
        try {
            const resposta = await fetch(cadastro.action, { method: 'POST', body: new FormData(cadastro), headers: { Accept: 'application/json' } });
            if (!resposta.ok) {
                const dados = await resposta.json().catch(() => ({}));
                throw new Error(dados.errors ? Object.values(dados.errors).flat().join(' ') : 'Não foi possível cadastrar. Verifique sua conexão e tente novamente.');
            }
            const dados = await resposta.json();
            const opcao = new Option(`${dados.nome} · ${dados.telefone}`, dados.id, true, true);
            opcao.dataset.cliente = JSON.stringify(dados);
            cliente.add(opcao);
            cliente.dispatchEvent(new Event('change', { bubbles: true }));
            cadastro.reset();
            cadastro.querySelector('[data-viacep]')?.dispatchEvent(new Event('input'));
            dialog.close();
        } catch (erro) {
            erros.textContent = erro.message;
            erros.hidden = false;
        } finally {
            botao.disabled = false;
        }
    });

})();

(() => {
    const dialog = document.getElementById('os-acao-dialog');
    if (!dialog) return;
    const conteudo = dialog.querySelector('[data-os-acao-conteudo]');
    let requisicao;
    let gatilho;
    let urlAcao;
    let alterado = false;
    let salvando = false;

    function atualizarRecebido(valor) {
        const campo = document.querySelector('[data-valor-pago]');
        if (!campo || typeof valor !== 'number') return;
        campo.dataset.valorPago = String(valor);
        campo.textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor);
        document.getElementById('ordem-form')?.dispatchEvent(new Event('gp:pagamentos'));
    }

    function atualizarPagina() {
        if (!document.getElementById('ordem-form')) window.location.reload();
    }

    function posicionarMenu(menu) {
        const botao = document.querySelector(`[popovertarget="${CSS.escape(menu.id)}"]`);
        if (!botao) return;
        const rect = botao.getBoundingClientRect();
        const abaixo = innerHeight - rect.bottom;
        const acima = rect.top;
        menu.style.left = `${Math.max(12, Math.min(rect.right - 230, innerWidth - 242))}px`;
        menu.style.maxHeight = `${Math.max(120, Math.max(abaixo, acima) - 18)}px`;
        if (abaixo < 300 && acima > abaixo) {
            menu.style.top = 'auto';
            menu.style.bottom = `${innerHeight - rect.top + 6}px`;
        } else {
            menu.style.bottom = 'auto';
            menu.style.top = `${rect.bottom + 6}px`;
        }
    }
    document.addEventListener('beforetoggle', (evento) => {
        if (evento.newState === 'open' && evento.target.matches('[data-os-menu]')) posicionarMenu(evento.target);
    }, true);

    function mostrarErro(mensagem) {
        let erro = conteudo.querySelector('[data-os-acao-erros]');
        if (!erro) {
            conteudo.replaceChildren();
            const titulo = document.createElement('h2');
            titulo.id = 'os-acao-titulo';
            titulo.textContent = 'Não foi possível abrir a ação';
            erro = document.createElement('div');
            erro.className = 'notice notice-error';
            erro.setAttribute('role', 'alert');
            erro.dataset.osAcaoErros = '';
            const fechar = document.createElement('button');
            fechar.type = 'button';
            fechar.className = 'button button-secondary fit';
            fechar.dataset.osAcaoFechar = '';
            fechar.textContent = 'Fechar';
            conteudo.append(titulo, erro, fechar);
        }
        erro.textContent = mensagem;
        erro.hidden = false;
        erro.scrollIntoView({ block: 'nearest' });
    }
    async function abrir(url, mensagem = '') {
        urlAcao = url;
        requisicao?.abort();
        const atual = new AbortController();
        requisicao = atual;
        conteudo.replaceChildren();
        const titulo = document.createElement('h2');
        titulo.id = 'os-acao-titulo';
        titulo.textContent = 'Carregando…';
        conteudo.append(titulo);
        dialog.setAttribute('aria-busy', 'true');
        dialog.classList.remove('osf-history-dialog');
        if (!dialog.open) dialog.showModal();
        try {
            const resposta = await fetch(url, { signal: atual.signal, headers: { Accept: 'text/html' } });
            if (resposta.redirected) { window.location.assign(resposta.url); return; }
            if (!resposta.ok) throw new Error('Não foi possível carregar esta ação. Feche e tente novamente.');
            const html = await resposta.text();
            if (requisicao !== atual) return;
            conteudo.innerHTML = html;
            dialog.classList.toggle('osf-history-dialog', Boolean(conteudo.querySelector('[data-cliente-historico]')));
            conteudo.querySelectorAll('[data-mask]').forEach((campo) => window.GPMasks?.aplicar(campo));
            window.GPWhatsApp?.inicializar(conteudo);
            const sucesso = conteudo.querySelector('[data-os-acao-sucesso]');
            if (sucesso && mensagem) {
                sucesso.textContent = mensagem;
                sucesso.hidden = false;
            }
            conteudo.querySelector('[data-os-acao-fechar]')?.focus();
        } catch (erro) {
            if (erro.name !== 'AbortError') mostrarErro(erro.message);
        } finally {
            if (requisicao === atual) dialog.removeAttribute('aria-busy');
        }
    }
    document.addEventListener('click', (evento) => {
        const acao = evento.target.closest('[data-os-acao], [data-cliente-historico] .pagination a');
        if (acao) {
            evento.preventDefault();
            if (salvando) return;
            const menu = acao.closest('[data-os-menu]');
            if (!dialog.contains(acao)) gatilho = menu ? document.querySelector(`[popovertarget="${CSS.escape(menu.id)}"]`) : acao;
            document.querySelectorAll('[data-os-menu]:popover-open').forEach((menu) => menu.hidePopover());
            abrir(acao.href);
        }
        if (evento.target.closest('[data-os-acao-fechar]')) dialog.close();
    });
    dialog.addEventListener('close', () => {
        requisicao?.abort();
        gatilho?.focus();
        if (alterado) atualizarPagina();
    });
    dialog.addEventListener('submit', async (evento) => {
        const form = evento.target;
        if (!(form instanceof HTMLFormElement)) return;
        evento.preventDefault();
        if (salvando || form.dataset.enviando === '1') return;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
        const urlSalva = urlAcao;
        const botao = evento.submitter;
        const dados = new FormData(form);
        const erros = conteudo.querySelector('[data-os-acao-erros]');
        if (erros) erros.hidden = true;
        salvando = true;
        form.dataset.enviando = '1';
        if (botao) botao.disabled = true;
        form.setAttribute('aria-busy', 'true');
        try {
            const resposta = await fetch(form.action, { method: form.method.toUpperCase(), body: dados, headers: { Accept: 'application/json' } });
            const resultado = await resposta.json().catch(() => ({}));
            if (!resposta.ok) {
                const mensagem = resultado.errors ? Object.values(resultado.errors).flat().join(' ') : resultado.message || 'Não foi possível salvar. Tente novamente.';
                throw new Error(mensagem);
            }
            alterado = true;
            atualizarRecebido(resultado.valor_pago);
            if (!dialog.open) {
                atualizarPagina();
            } else if (urlAcao === urlSalva) {
                await abrir(urlSalva, resultado.message || 'Alterações salvas.');
            }
        } catch (erro) {
            if (form.isConnected) mostrarErro(erro.message);
        } finally {
            salvando = false;
            form.dataset.enviando = '0';
            form.removeAttribute('aria-busy');
            if (botao) botao.disabled = false;
        }
    });
    const automatica = document.querySelector('[data-os-acao-auto]');
    if (automatica) {
        abrir(automatica.dataset.osAcaoAuto);
        const url = new URL(window.location.href);
        url.searchParams.delete('whatsapp');
        history.replaceState(null, '', url);
    }
})();

(() => {
    const normalizar = (valor) => String(valor || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    document.querySelectorAll('select[data-searchable-select]').forEach((select) => {
        const root = document.createElement('div');
        root.className = 'osf-combobox';
        const controle = document.createElement('div');
        controle.className = 'osf-combobox-control';
        const input = document.createElement('input');
        input.id = `${select.id}-combobox`;
        input.type = 'text';
        input.className = 'osf-combobox-input';
        input.placeholder = select.dataset.placeholder || 'Digite para pesquisar';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.required = select.required;
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', `${select.id}-opcoes`);
        if (select.getAttribute('aria-describedby')) input.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
        const limpar = document.createElement('button');
        limpar.type = 'button';
        limpar.className = 'osf-combobox-clear';
        limpar.tabIndex = -1;
        limpar.setAttribute('aria-label', 'Limpar cliente selecionado');
        limpar.textContent = '×';
        const abrir = document.createElement('button');
        abrir.type = 'button';
        abrir.className = 'osf-combobox-toggle';
        abrir.tabIndex = -1;
        abrir.setAttribute('aria-label', 'Mostrar clientes');
        abrir.setAttribute('aria-controls', `${select.id}-opcoes`);
        abrir.textContent = '⌄';
        const lista = document.createElement('ul');
        lista.id = `${select.id}-opcoes`;
        lista.className = 'osf-combobox-options';
        lista.setAttribute('role', 'listbox');
        lista.setAttribute('aria-label', 'Clientes encontrados');
        lista.hidden = true;
        const feedback = document.createElement('span');
        feedback.className = 'sr-only';
        feedback.setAttribute('role', 'status');
        controle.append(input, limpar, abrir);
        root.append(controle, lista, feedback);
        select.after(root);
        [...select.labels].forEach((label) => { label.htmlFor = input.id; });
        select.hidden = true;
        select.required = false;
        let resultados = [];
        let ativo = -1;

        const rotulo = () => select.value ? select.selectedOptions[0]?.textContent.trim() || '' : '';
        const validar = () => input.setCustomValidity((select.value && input.value === rotulo()) || (!input.required && !input.value) ? '' : 'Selecione um cliente da lista.');
        function fechar(restaurar = false) {
            lista.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            if (restaurar) input.value = rotulo();
            validar();
        }
        function sincronizar() {
            input.value = rotulo();
            limpar.hidden = !select.value;
            input.removeAttribute('aria-invalid');
            input.classList.remove('is-invalid');
            fechar();
        }
        function destacar(indice) {
            ativo = indice;
            [...lista.querySelectorAll('[role="option"]')].forEach((item, i) => {
                item.setAttribute('aria-selected', String(i === ativo));
            });
            const item = lista.querySelectorAll('[role="option"]')[ativo];
            if (item) {
                input.setAttribute('aria-activedescendant', item.id);
                item.scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }
        function escolher(opcao) {
            if (!opcao) return;
            select.value = opcao.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
            sincronizar();
        }
        function mostrar(termo = '') {
            const texto = normalizar(termo).trim();
            const digitos = texto.replace(/\D/g, '');
            resultados = [...select.options].filter((opcao) => {
                if (!opcao.value || opcao.disabled) return false;
                const dados = JSON.parse(opcao.dataset.cliente || '{}');
                const busca = normalizar(`${opcao.textContent} ${dados.documento || ''}`);
                return busca.includes(texto) || (digitos.length >= 3 && [dados.telefone, dados.documento].some((valor) => String(valor || '').replace(/\D/g, '').includes(digitos)));
            });
            lista.replaceChildren();
            resultados.forEach((opcao, indice) => {
                const dados = JSON.parse(opcao.dataset.cliente || '{}');
                const item = document.createElement('li');
                item.id = `${select.id}-opcao-${opcao.value}`;
                item.tabIndex = -1;
                item.setAttribute('role', 'option');
                const nome = document.createElement('strong');
                nome.textContent = dados.nome || opcao.textContent;
                const detalhe = document.createElement('small');
                detalhe.textContent = [dados.telefone ? window.GPMasks?.maskTelefone(dados.telefone) || dados.telefone : '', dados.documento ? window.GPMasks?.maskCpfCnpj(dados.documento) || dados.documento : ''].filter(Boolean).join(' · ');
                item.append(nome, detalhe);
                item.addEventListener('mousedown', (evento) => evento.preventDefault());
                item.addEventListener('click', () => escolher(opcao));
                item.addEventListener('pointermove', () => destacar(indice));
                lista.append(item);
            });
            if (!resultados.length) {
                const vazio = document.createElement('li');
                vazio.className = 'osf-combobox-empty';
                vazio.setAttribute('role', 'presentation');
                vazio.textContent = 'Nenhum cliente encontrado. Use “Cadastrar cliente aqui” para adicionar.';
                lista.append(vazio);
            }
            lista.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            feedback.textContent = `${resultados.length} clientes encontrados.`;
            destacar(resultados.length ? 0 : -1);
        }
        input.addEventListener('focus', () => { mostrar(); input.select(); });
        input.addEventListener('click', () => { if (lista.hidden) mostrar(); });
        input.addEventListener('input', () => {
            if (!input.value && select.value) {
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            validar();
            mostrar(input.value);
        });
        input.addEventListener('invalid', () => { input.setAttribute('aria-invalid', 'true'); mostrar(input.value === rotulo() ? '' : input.value); });
        input.addEventListener('keydown', (evento) => {
            if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
                evento.preventDefault();
                if (lista.hidden) {
                    mostrar();
                    destacar(evento.key === 'ArrowUp' ? resultados.length - 1 : 0);
                } else if (resultados.length) {
                    destacar((ativo + (evento.key === 'ArrowDown' ? 1 : -1) + resultados.length) % resultados.length);
                }
            } else if (evento.key === 'Enter' && !lista.hidden) {
                evento.preventDefault();
                escolher(resultados[ativo]);
            } else if (evento.key === 'Escape' && !lista.hidden) {
                evento.preventDefault();
                evento.stopPropagation();
                fechar(true);
            } else if (evento.key === 'Tab') {
                fechar(true);
            }
        });
        [abrir, limpar].forEach((botao) => botao.addEventListener('mousedown', (evento) => evento.preventDefault()));
        abrir.addEventListener('click', () => {
            const aberta = !lista.hidden;
            input.focus();
            if (aberta) fechar(true); else mostrar();
        });
        limpar.addEventListener('click', () => {
            select.value = '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
            mostrar();
        });
        select.addEventListener('change', sincronizar);
        select.form?.addEventListener('reset', () => queueMicrotask(sincronizar));
        document.addEventListener('pointerdown', (evento) => { if (!root.contains(evento.target)) fechar(true); });
        document.addEventListener('focusin', (evento) => { if (!root.contains(evento.target)) fechar(true); });
        sincronizar();
    });
})();

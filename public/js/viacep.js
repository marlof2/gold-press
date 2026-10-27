(() => {
    const M = window.GPMasks;
    if (!M) return;

    document.querySelectorAll('[data-viacep]').forEach((cep) => {
        const form = cep.form;
        if (!form) return;

        const spinner = form.querySelector('[data-cep-spinner]');
        const feedback = form.querySelector('[data-cep-feedback]');
        let requisicaoAtual;
        let ultimoCep;

        const informar = (mensagem, estado = '') => {
            if (!feedback) return;
            feedback.textContent = mensagem;
            feedback.className = `osf-help${estado ? ` osf-help-${estado}` : ''}`;
        };

        async function buscarCep() {
            const digitos = M.onlyDigits(cep.value);
            if (digitos.length !== 8 || ultimoCep === digitos) return;

            ultimoCep = digitos;
            requisicaoAtual?.abort();
            const requisicao = new AbortController();
            requisicaoAtual = requisicao;
            const temporizador = window.setTimeout(() => requisicao.abort(), 8000);

            if (spinner) spinner.hidden = false;
            cep.setAttribute('aria-busy', 'true');
            informar('Buscando endereço...');

            try {
                const resposta = await fetch(`https://viacep.com.br/ws/${digitos}/json/`, { signal: requisicao.signal });
                if (!resposta.ok) throw new Error('Falha');
                const dados = await resposta.json();
                if (dados.erro) throw new Error('Não encontrado');
                if (requisicaoAtual !== requisicao || M.onlyDigits(cep.value) !== digitos) return;

                Object.entries({ endereco: dados.logradouro, bairro: dados.bairro, cidade: dados.localidade, uf: dados.uf }).forEach(([nome, valor]) => {
                    const campo = form.elements.namedItem(nome);
                    if (campo) campo.value = valor ?? '';
                });
                informar('Endereço preenchido pelo CEP.', 'success');
                form.elements.namedItem('numero_endereco')?.focus();
            } catch (erro) {
                if (requisicaoAtual !== requisicao || M.onlyDigits(cep.value) !== digitos) return;
                ultimoCep = undefined;
                informar('CEP não encontrado. Preencha o endereço manualmente.', 'error');
            } finally {
                window.clearTimeout(temporizador);
                if (requisicaoAtual === requisicao) {
                    requisicaoAtual = undefined;
                    if (spinner) spinner.hidden = true;
                    cep.removeAttribute('aria-busy');
                }
            }
        }

        cep.addEventListener('blur', buscarCep);
        cep.addEventListener('input', () => {
            if (M.onlyDigits(cep.value) !== ultimoCep) {
                requisicaoAtual?.abort();
                requisicaoAtual = undefined;
                ultimoCep = undefined;
                if (spinner) spinner.hidden = true;
                cep.removeAttribute('aria-busy');
                informar('');
            }
            buscarCep();
        });
    });
})();

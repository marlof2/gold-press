(() => {
    const form = document.getElementById('ordem-form');
    if (!form) return;

    const M = window.GPMasks;
    if (!M) return;

    const container = document.getElementById('osf-itens');
    const template = document.getElementById('osf-item-template');
    const toasts = document.getElementById('osf-toasts');
    const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const areaFmt = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
    const qtdFmt = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 4 });

    const numero = (valor) => M.parseNumero(valor);
    const arredondar = (valor, casas = 2) => Math.sign(valor) * Math.round((Math.abs(valor) + Number.EPSILON) * 10 ** casas) / 10 ** casas;
    const texto = (seletor, valor) => {
        const el = document.querySelector(seletor);
        if (el) el.textContent = valor;
    };
    const valorDe = (id, valor) => {
        const el = document.getElementById(id);
        if (el) el.value = valor ?? '';
    };

    let proximoIndice = 0;
    let sujo = false;
    let clienteRequest;
    let cepRequest;

    container.querySelectorAll('[data-item]').forEach((row) => {
        proximoIndice = Math.max(proximoIndice, Number(row.dataset.index || 0) + 1);
    });

    function toast(mensagem, opcoes = {}) {
        if (!toasts) return;
        const el = document.createElement('div');
        el.className = 'osf-toast';
        el.setAttribute('role', 'status');
        const span = document.createElement('span');
        span.textContent = mensagem;
        el.appendChild(span);
        if (opcoes.acao) {
            const botao = document.createElement('button');
            botao.type = 'button';
            botao.textContent = opcoes.rotulo || 'Desfazer';
            botao.addEventListener('click', () => {
                opcoes.acao();
                el.remove();
            });
            el.appendChild(botao);
        }
        toasts.appendChild(el);
        window.setTimeout(() => el.remove(), 6000);
    }

    function renomear(root, de, para) {
        root.querySelectorAll('*').forEach((el) => {
            [...el.attributes].forEach((attr) => {
                if (['name', 'id', 'for', 'data-target', 'aria-describedby'].includes(attr.name)) {
                    el.setAttribute(attr.name, attr.value.replaceAll(`[${de}]`, `[${para}]`).replaceAll(`-${de}-`, `-${para}-`));
                }
            });
        });
    }

    function prepararLinha(row) {
        row.querySelectorAll('[data-mask]').forEach((campo) => {
            delete campo.dataset.maskAplicada;
        });
        row.querySelectorAll('input, select, textarea').forEach((campo) => {
            delete campo.dataset.validacao;
        });
    }

    function validarCampo(campo) {
        if (campo.dataset.validacao === '1') return;
        campo.dataset.validacao = '1';
        const verificar = () => {
            if (campo.disabled) {
                campo.classList.remove('is-invalid', 'is-valid');
                return;
            }
            const valido = campo.checkValidity();
            const preenchido = campo.type === 'checkbox' || String(campo.value).trim() !== '';
            campo.classList.toggle('is-invalid', !valido);
            campo.classList.toggle('is-valid', valido && preenchido);
            campo.setAttribute('aria-invalid', String(!valido));
        };
        campo.addEventListener('blur', verificar);
        campo.addEventListener('input', () => {
            if (campo.classList.contains('is-invalid')) verificar();
        });
    }

    function alternarTipo(row) {
        const tipo = row.querySelector('[data-tipo-item]:checked')?.value || 'unitario';
        row.querySelectorAll('.osf-segmented label').forEach((label) => {
            const radio = label.querySelector('input[data-tipo-item]');
            label.classList.toggle('is-active', Boolean(radio?.checked));
        });
        row.querySelectorAll('[data-campos]').forEach((campos) => {
            const ativo = campos.dataset.campos === tipo;
            campos.hidden = !ativo;
            campos.querySelectorAll('input, select, textarea').forEach((campo) => {
                campo.disabled = !ativo;
                if (campo.dataset.obrigatorio === '1') campo.required = ativo;
            });
            if (ativo) {
                const unidade = campos.querySelector('input[name$="[unidade]"]');
                if (unidade && (!unidade.value || unidade.value === 'un' || unidade.value === 'm²')) {
                    unidade.value = tipo === 'm2' ? 'm²' : 'un';
                }
            }
        });
    }

    function selecionarProduto(row) {
        const campo = row.querySelector('[data-produto]');
        const oculto = row.querySelector('[data-produto-id]');
        if (!campo || !oculto) return;

        const opcao = [...document.querySelectorAll('#produtos-lista option')].find((item) => item.value.trim().toLowerCase() === campo.value.trim().toLowerCase());
        if (!opcao) {
            oculto.value = '';
            return;
        }

        oculto.value = opcao.dataset.id || '';
        const tipo = opcao.dataset.tipo === 'm2' ? 'm2' : 'unitario';
        const radio = row.querySelector(`[data-tipo-item][value="${tipo}"]`);
        if (radio) radio.checked = true;
        alternarTipo(row);

        const campos = row.querySelector(`[data-campos="${tipo}"]`);
        if (campos) {
            const unidade = campos.querySelector('input[name$="[unidade]"]');
            if (unidade) unidade.value = opcao.dataset.unidade || (tipo === 'm2' ? 'm²' : 'un');
            if (tipo === 'm2') {
                const valor = campos.querySelector('[data-campo="valor_m2"]');
                if (valor) valor.value = M.formatMoeda(opcao.dataset.valorM2);
            } else {
                const valor = campos.querySelector('[data-campo="valor_unitario"]');
                if (valor) valor.value = M.formatMoeda(opcao.dataset.valorUnitario);
            }
        }

        calcular();
    }

    function calcular() {
        let totalQuantidadeUnitario = 0;
        let totalQuantidadeM2 = 0;
        let totalArea = 0;
        let subtotal = 0;
        const rows = [...container.querySelectorAll('[data-item]')];

        rows.forEach((row) => {
            const tipo = row.querySelector('[data-tipo-item]:checked')?.value || 'unitario';
            const quantidade = numero(row.querySelector('[data-campo="quantidade"]')?.value);
            let valor = 0;

            if (tipo === 'm2') {
                totalQuantidadeM2 += quantidade;
                const largura = numero(row.querySelector('[data-campo="largura"]')?.value);
                const altura = numero(row.querySelector('[data-campo="altura"]')?.value);
                const area = arredondar(largura * altura, 4);
                const valorM2 = numero(row.querySelector('[data-campo="valor_m2"]')?.value);
                totalArea += area;
                valor = arredondar(area * quantidade * valorM2, 2);
                const areaEl = row.querySelector('[data-area]');
                if (areaEl) areaEl.textContent = `${areaFmt.format(area)} m²`;
            } else {
                totalQuantidadeUnitario += quantidade;
                const valorUnitario = numero(row.querySelector('[data-campo="valor_unitario"]')?.value);
                valor = arredondar(quantidade * valorUnitario, 2);
            }

            subtotal += valor;
            const subtotalEl = row.querySelector('[data-subtotal]');
            if (subtotalEl) subtotalEl.textContent = money.format(valor);
        });

        subtotal = arredondar(subtotal, 2);
        const desconto = numero(document.getElementById('desconto')?.value);
        const produtos = subtotal;
        const total = arredondar(subtotal - desconto, 2);

        texto('[data-resumo-itens]', String(rows.length));
        texto('[data-resumo-quantidade-unitario]', qtdFmt.format(totalQuantidadeUnitario));
        texto('[data-resumo-quantidade-m2]', qtdFmt.format(totalQuantidadeM2));
        texto('[data-resumo-area]', `${areaFmt.format(totalArea)} m²`);
        texto('[data-resumo-subtotal]', money.format(subtotal));
        texto('[data-resumo-total]', money.format(total));
        texto('[data-resumo-total-mobile]', money.format(total));

        const invalido = produtos < 0 || total < 0;
        const erro = document.querySelector('[data-resumo-erro]');
        if (erro) erro.hidden = !invalido;
        document.querySelectorAll('[data-submit]').forEach((botao) => {
            botao.disabled = invalido;
        });
    }

    function atualizarNumeros() {
        const rows = [...container.querySelectorAll('[data-item]')];
        rows.forEach((row, indice) => {
            const numeroEl = row.querySelector('[data-item-numero]');
            if (numeroEl) numeroEl.textContent = `Item ${indice + 1}`;
            const remover = row.querySelector('[data-item-remover]');
            if (remover) {
                remover.disabled = rows.length <= 1;
                remover.title = rows.length <= 1 ? 'A OS precisa de pelo menos um item' : 'Remover item';
            }
        });
    }

    function inicializarLinha(row) {
        row.querySelectorAll('[data-mask]').forEach((campo) => M.aplicar(campo));
        row.querySelectorAll('input, select, textarea').forEach(validarCampo);
        row.querySelectorAll('[data-campo]').forEach((campo) => campo.addEventListener('input', calcular));
        row.querySelectorAll('[data-tipo-item]').forEach((radio) => {
            radio.addEventListener('change', () => {
                alternarTipo(row);
                calcular();
            });
        });
        const produto = row.querySelector('[data-produto]');
        produto?.addEventListener('change', () => selecionarProduto(row));
        produto?.addEventListener('input', () => selecionarProduto(row));
        row.querySelector('[data-item-remover]')?.addEventListener('click', () => removerItem(row));
        row.querySelector('[data-item-duplicar]')?.addEventListener('click', () => duplicarItem(row));
        alternarTipo(row);
    }

    function adicionarItem() {
        if (!template) return;
        const html = template.innerHTML.replaceAll('__INDEX__', String(proximoIndice));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;
        if (!row) return;
        container.appendChild(row);
        proximoIndice += 1;
        inicializarLinha(row);
        atualizarNumeros();
        calcular();
        row.querySelector('[data-produto]')?.focus();
        sujo = true;
    }

    function duplicarItem(row) {
        const novoIndice = proximoIndice;
        proximoIndice += 1;
        const clone = row.cloneNode(true);
        renomear(clone, row.dataset.index, novoIndice);
        clone.dataset.index = novoIndice;
        prepararLinha(clone);
        container.insertBefore(clone, row.nextElementSibling);
        inicializarLinha(clone);
        atualizarNumeros();
        calcular();
        sujo = true;
    }

    function removerItem(row) {
        const rows = container.querySelectorAll('[data-item]');
        if (rows.length <= 1) {
            toast('A OS precisa de pelo menos um item.');
            return;
        }
        const proximo = row.nextElementSibling;
        const html = row.outerHTML;
        row.remove();
        atualizarNumeros();
        calcular();
        sujo = true;
        toast('Item removido.', {
            rotulo: 'Desfazer',
            acao: () => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const nova = wrapper.firstElementChild;
                if (!nova) return;
                if (proximo && proximo.parentElement === container) {
                    container.insertBefore(nova, proximo);
                } else {
                    container.appendChild(nova);
                }
                prepararLinha(nova);
                inicializarLinha(nova);
                atualizarNumeros();
                calcular();
            },
        });
    }

    const cliente = document.getElementById('cliente_id');
    const clienteFeedback = document.getElementById('cliente-feedback');
    const usarEndereco = document.getElementById('usar-endereco-cliente');

    function preencherCliente(dados) {
        const telefone = document.getElementById('cliente-telefone');
        const documento = document.getElementById('cliente-documento');
        if (telefone) telefone.textContent = dados.telefone || '';
        if (documento) documento.textContent = dados.documento || '';
        if (usarEndereco?.checked) {
            valorDe('endereco', dados.endereco);
            valorDe('bairro', dados.bairro);
            valorDe('cidade', dados.cidade);
            valorDe('uf', dados.uf);
            valorDe('cep', dados.cep ? M.maskCep(dados.cep) : '');
        }
    }

    async function carregarCliente() {
        if (!cliente?.value) {
            preencherCliente({});
            return;
        }
        clienteRequest?.abort();
        clienteRequest = new AbortController();
        if (clienteFeedback) clienteFeedback.textContent = '';
        try {
            const resposta = await fetch(`${cliente.dataset.url}/${cliente.value}/dados`, { signal: clienteRequest.signal, headers: { Accept: 'application/json' } });
            if (!resposta.ok) throw new Error('Falha');
            preencherCliente(await resposta.json());
            if (clienteFeedback) {
                clienteFeedback.textContent = '';
                clienteFeedback.className = 'osf-help';
            }
        } catch (erro) {
            if (erro.name !== 'AbortError' && clienteFeedback) {
                clienteFeedback.textContent = 'Não foi possível carregar os dados do cliente.';
                clienteFeedback.className = 'osf-help osf-help-error';
            }
        }
    }

    cliente?.addEventListener('change', carregarCliente);
    if (cliente?.value && !document.getElementById('cliente-telefone')?.textContent.trim()) {
        carregarCliente();
    }

    const cep = document.getElementById('cep');
    const cepSpinner = document.querySelector('[data-cep-spinner]');
    const cepFeedback = document.querySelector('[data-cep-feedback]');

    async function buscarCep() {
        if (!cep) return;
        const digitos = M.onlyDigits(cep.value);
        if (digitos.length !== 8 || cep.dataset.ultimoCep === digitos) return;

        cep.dataset.ultimoCep = digitos;
        cepRequest?.abort();
        const requisicao = new AbortController();
        cepRequest = requisicao;
        const temporizador = window.setTimeout(() => requisicao.abort(), 8000);

        if (cepSpinner) cepSpinner.hidden = false;
        cep.setAttribute('aria-busy', 'true');
        if (cepFeedback) {
            cepFeedback.textContent = 'Buscando endereço...';
            cepFeedback.className = 'osf-help';
        }

        try {
            const resposta = await fetch(`https://viacep.com.br/ws/${digitos}/json/`, { signal: requisicao.signal });
            if (!resposta.ok) throw new Error('Falha');
            const dados = await resposta.json();
            if (dados.erro) throw new Error('Não encontrado');
            valorDe('endereco', dados.logradouro);
            valorDe('bairro', dados.bairro);
            valorDe('cidade', dados.localidade);
            valorDe('uf', dados.uf);
            if (cepFeedback) {
                cepFeedback.textContent = 'Endereço preenchido pelo CEP.';
                cepFeedback.className = 'osf-help osf-help-success';
            }
            document.getElementById('numero_endereco')?.focus();
        } catch (erro) {
            if (M.onlyDigits(cep.value) !== digitos) return;
            delete cep.dataset.ultimoCep;
            if (cepFeedback) {
                cepFeedback.textContent = 'CEP não encontrado. Preencha o endereço manualmente.';
                cepFeedback.className = 'osf-help osf-help-error';
            }
        } finally {
            window.clearTimeout(temporizador);
            if (cepRequest === requisicao) {
                if (cepSpinner) cepSpinner.hidden = true;
                cep.removeAttribute('aria-busy');
            }
        }
    }

    cep?.addEventListener('blur', buscarCep);
    cep?.addEventListener('input', () => {
        if (M.onlyDigits(cep.value).length === 8) buscarCep();
    });

    document.querySelectorAll('[data-ajuste]').forEach((campo) => campo.addEventListener('input', calcular));
    form.querySelectorAll('input, select, textarea').forEach(validarCampo);
    document.querySelectorAll('[data-item-adicionar]').forEach((botao) => botao.addEventListener('click', adicionarItem));

    container.querySelectorAll('[data-item]').forEach(inicializarLinha);
    atualizarNumeros();
    calcular();

    form.addEventListener('input', () => {
        sujo = true;
    });
    form.addEventListener('submit', () => {
        sujo = false;
        document.querySelectorAll('[data-submit]').forEach((botao) => {
            botao.classList.add('is-loading');
            botao.setAttribute('aria-disabled', 'true');
        });
    });
    window.addEventListener('beforeunload', (evento) => {
        if (!sujo) return;
        evento.preventDefault();
        evento.returnValue = '';
    });
    document.querySelector('[data-cancelar]')?.addEventListener('click', (evento) => {
        if (!sujo) return;
        if (!window.confirm('Descartar as alterações desta OS?')) {
            evento.preventDefault();
        } else {
            sujo = false;
        }
    });
})();

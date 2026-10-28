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
        const recebido = numero(document.getElementById('sinal')?.value) + Number(document.querySelector('[data-valor-pago]')?.dataset.valorPago || 0);
        texto('[data-resumo-saldo]', money.format(Math.max(0, total - recebido)));

        const invalido = produtos < 0 || total < 0 || total < recebido;
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

    function preencherCliente(dados, preencherEndereco = true) {
        const telefone = document.getElementById('cliente-telefone');
        const documento = document.getElementById('cliente-documento');
        if (telefone) telefone.textContent = dados.telefone ? M.maskTelefone(dados.telefone) : '—';
        if (documento) documento.textContent = dados.documento ? M.maskCpfCnpj(dados.documento) : '—';
        const documentoLabel = document.getElementById('cliente-documento-label');
        if (documentoLabel) documentoLabel.textContent = dados.documento ? (M.onlyDigits(dados.documento).length === 14 ? 'CNPJ' : 'CPF') : 'CPF / CNPJ';
        if (preencherEndereco) {
            valorDe('endereco', dados.endereco);
            valorDe('bairro', dados.bairro);
            valorDe('cidade', dados.cidade);
            valorDe('uf', dados.uf);
            valorDe('cep', dados.cep ? M.maskCep(dados.cep) : '');
            valorDe('numero_endereco', dados.numero_endereco);
        }
        form.dispatchEvent(new CustomEvent('gp:cliente', { detail: dados }));
    }

    function carregarCliente(preservarEndereco = false) {
        try {
            const dados = JSON.parse(cliente?.selectedOptions[0]?.dataset.cliente || '{}');
            preencherCliente(dados, !preservarEndereco);
            if (clienteFeedback) clienteFeedback.textContent = '';
        } catch {
            if (clienteFeedback) clienteFeedback.textContent = 'Selecione novamente o cliente.';
        }
    }

    document.querySelector('[data-endereco-limpar]')?.addEventListener('click', () => {
        ['cep', 'endereco', 'numero_endereco', 'bairro', 'cidade', 'uf'].forEach((id) => {
            const campo = document.getElementById(id);
            if (!campo) return;
            campo.value = '';
            campo.classList.remove('is-valid', 'is-invalid');
            campo.removeAttribute('aria-invalid');
            campo.dispatchEvent(new Event('input', { bubbles: true }));
        });
        document.getElementById('cep')?.focus();
    });

    cliente?.addEventListener('change', (evento) => carregarCliente(evento.detail?.preservarEndereco === true));
    carregarCliente(form.dataset.preservarEndereco === '1');
    form.addEventListener('gp:pagamentos', calcular);
    document.querySelectorAll('[data-ajuste]').forEach((campo) => campo.addEventListener('input', calcular));
    form.querySelectorAll('input, select, textarea').forEach(validarCampo);
    document.querySelectorAll('[data-item-adicionar]').forEach((botao) => botao.addEventListener('click', adicionarItem));

    container.querySelectorAll('[data-item]').forEach(inicializarLinha);
    atualizarNumeros();
    calcular();

    form.addEventListener('input', () => {
        sujo = true;
    });
    let enviando = false;
    form.addEventListener('submit', (evento) => {
        if (enviando) { evento.preventDefault(); return; }
        enviando = true;
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

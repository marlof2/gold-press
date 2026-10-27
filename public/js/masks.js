(() => {
    const digits = (valor) => String(valor ?? '').replace(/\D/g, '');

    const maskCep = (valor) => digits(valor).slice(0, 8).replace(/^(\d{5})(\d)/, '$1-$2');

    const maskTelefone = (valor) => {
        const d = digits(valor).slice(0, 11);
        if (d.length === 0) return '';
        if (d.length <= 2) return `(${d}`;
        if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
        if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
        return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
    };

    const maskCpfCnpj = (valor) => {
        const d = digits(valor).slice(0, 14);
        if (d.length <= 11) {
            return d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        return d.replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    };

    const parseNumero = (valor) => {
        let texto = String(valor ?? '').replace(/[^\d,.-]/g, '').trim();
        if (texto.includes(',')) {
            texto = texto.replace(/\./g, '').replace(',', '.');
        }
        const numero = Number(texto);
        return Number.isFinite(numero) ? numero : 0;
    };

    const formatMoeda = (valor) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(valor) || 0);

    const maskMoeda = (valor) => formatMoeda(parseNumero(valor));

    const maskDecimal = (valor) => {
        let texto = String(valor ?? '').replace(/[^\d,.-]/g, '').replace(/\./g, ',');
        const partes = texto.split(',');
        const inteiro = partes.shift() ?? '';
        if (partes.length === 0) return inteiro;
        return `${inteiro},${partes.join('').slice(0, 4)}`;
    };

    const mascaras = { cep: maskCep, telefone: maskTelefone, 'cpf-cnpj': maskCpfCnpj, currency: maskMoeda, decimal: maskDecimal };

    const aplicar = (input) => {
        const tipo = input.dataset.mask;
        const mascara = mascaras[tipo];
        if (!mascara || input.dataset.maskAplicada === '1') return;
        input.dataset.maskAplicada = '1';

        const formatar = () => {
            input.value = mascara(input.value);
        };
        const sanitizarMoeda = (valor) => String(valor ?? '').replace(/[^\d,.-]/g, '');

        input.addEventListener('input', () => {
            if (tipo === 'currency') {
                input.value = sanitizarMoeda(input.value);
            } else {
                formatar();
            }
        });
        input.addEventListener('blur', () => {
            if (tipo === 'decimal') {
                input.value = input.value.replace(/,$/, '');
            }
            formatar();
        });
        if (tipo === 'currency') {
            input.addEventListener('focus', () => input.select());
        }
        if (input.value) formatar();
    };

    document.querySelectorAll('[data-mask]').forEach(aplicar);

    window.GPMasks = { aplicar, maskCep, maskTelefone, maskCpfCnpj, parseNumero, formatMoeda, maskMoeda, maskDecimal, onlyDigits: digits };
})();

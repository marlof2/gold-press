(() => {
    document.querySelectorAll('[data-tipo-pessoa]').forEach((tipo) => {
        const form = tipo.form;
        const documento = form?.elements.namedItem('documento');
        if (!documento) return;

        function atualizar() {
            const juridica = tipo.value === 'PJ';
            const rotulo = juridica ? 'CNPJ' : 'CPF';
            [...documento.labels].forEach((label) => { label.textContent = rotulo; });
            documento.placeholder = juridica ? '00.000.000/0000-00' : '000.000.000-00';
            const digitos = documento.value.replace(/\D/g, '');
            documento.setCustomValidity(digitos && digitos.length !== (juridica ? 14 : 11)
                ? `Informe um ${rotulo} com ${juridica ? 14 : 11} dígitos.` : '');
        }

        tipo.addEventListener('change', atualizar);
        documento.addEventListener('input', atualizar);
        form.addEventListener('reset', () => queueMicrotask(atualizar));
        atualizar();
    });
})();

(() => {
    function inicializar(root = document) {
        root.querySelectorAll('[data-whatsapp-editor]').forEach((editor) => {
            if (editor.dataset.inicializado === '1') return;
            editor.dataset.inicializado = '1';
            const modelos = JSON.parse(editor.querySelector('[data-whatsapp-modelos]').textContent);
            const tipo = editor.querySelector('[data-whatsapp-modelo]');
            const mensagem = editor.querySelector('[data-whatsapp-mensagem]');
            const abrir = editor.querySelector('[data-whatsapp-abrir]');
            const feedback = editor.querySelector('[data-whatsapp-feedback]');
            const atualizar = () => {
                if (abrir) abrir.href = `https://wa.me/${editor.dataset.telefone}?text=${encodeURIComponent(mensagem.value)}`;
            };
            tipo.addEventListener('change', () => { mensagem.value = modelos[tipo.value] || ''; atualizar(); });
            mensagem.addEventListener('input', atualizar);
            editor.querySelector('[data-whatsapp-copiar]').addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(mensagem.value);
                    feedback.textContent = 'Mensagem copiada.';
                } catch {
                    mensagem.focus();
                    mensagem.select();
                    feedback.textContent = 'Selecione a opção Copiar do seu navegador para copiar o texto.';
                }
            });
            atualizar();
        });
    }
    window.GPWhatsApp = { inicializar };
    inicializar();
})();

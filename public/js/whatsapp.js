(() => {
    function inicializar(root = document) {
        root.querySelectorAll('[data-whatsapp-previa]').forEach((editor) => {
            if (editor.dataset.inicializado === '1') return;
            editor.dataset.inicializado = '1';
            const campo = editor.querySelector('[name="conteudo"]');
            const previa = editor.querySelector('[data-whatsapp-previa-texto]');
            const aviso = editor.querySelector('[data-whatsapp-previa-aviso]');
            const dados = JSON.parse(editor.querySelector('[data-whatsapp-previa-dados]').textContent);
            const atualizar = () => {
                const desconhecidos = new Set();
                const texto = campo.value.replace(/\{([^{}\r\n]+)\}/g, (original, chave) => {
                    if (Object.hasOwn(dados, chave)) return dados[chave];
                    desconhecidos.add(original);
                    return original;
                });
                previa.replaceChildren();
                const partes = (texto || 'Digite uma mensagem para ver a prévia.').split(/(```[^`]+```|\*[^*\n]+\*|_[^_\n]+_|~[^~\n]+~)/g);
                partes.forEach((parte) => {
                    const tag = /^```[^`]+```$/.test(parte) ? 'code'
                        : /^\*[^*\n]+\*$/.test(parte) ? 'strong'
                        : /^_[^_\n]+_$/.test(parte) ? 'em'
                        : /^~[^~\n]+~$/.test(parte) ? 's' : null;
                    if (!tag) {
                        previa.append(document.createTextNode(parte));
                        return;
                    }
                    const elemento = document.createElement(tag);
                    const limite = tag === 'code' ? 3 : 1;
                    elemento.textContent = parte.slice(limite, -limite);
                    previa.append(elemento);
                });
                aviso.textContent = desconhecidos.size
                    ? `Campos não reconhecidos: ${[...desconhecidos].join(', ')}. Confira os campos disponíveis abaixo.`
                    : '';
            };
            campo.addEventListener('input', atualizar);
            atualizar();
        });
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

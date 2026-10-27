import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import vm from 'node:vm';

const codigo = readFileSync(new URL('../../public/js/viacep.js', import.meta.url), 'utf8');
const mascaras = readFileSync(new URL('../../public/js/masks.js', import.meta.url), 'utf8');
const endereco = { logradouro: 'Praça da Sé', bairro: 'Sé', localidade: 'São Paulo', uf: 'SP' };
const concluir = () => new Promise((resolve) => setImmediate(resolve));

function formulario({ numero = false } = {}) {
    const cep = new EventTarget();
    const atributos = new Map();
    cep.setAttribute = (nome, valor) => atributos.set(nome, valor);
    cep.removeAttribute = (nome) => atributos.delete(nome);
    const campos = Object.fromEntries(['endereco', 'bairro', 'cidade', 'uf'].map((nome) => [nome, { value: 'Anterior' }]));
    let focouNumero = false;
    if (numero) campos.numero_endereco = { focus: () => { focouNumero = true; } };
    const spinner = { hidden: true };
    const feedback = { textContent: '', className: '' };
    const consultas = [];
    const temporizadores = new Map();
    cep.form = {
        elements: { namedItem: (nome) => campos[nome] },
        querySelector: (seletor) => seletor === '[data-cep-spinner]' ? spinner : feedback,
    };
    const contexto = {
        AbortController,
        Intl,
        document: { querySelectorAll: (seletor) => seletor === '[data-viacep]' ? [cep] : [] },
        window: {
            setTimeout: (callback) => { const id = Symbol(); temporizadores.set(id, callback); return id; },
            clearTimeout: (id) => temporizadores.delete(id),
        },
        fetch: (url, { signal }) => new Promise((resolve, reject) => {
            consultas.push({ url, signal, resolve: (dados, ok = true) => resolve({ ok, json: async () => dados }), reject });
            signal.addEventListener('abort', () => reject(Object.assign(new Error('Cancelado'), { name: 'AbortError' })));
        }),
    };
    vm.runInNewContext(mascaras, contexto);
    vm.runInNewContext(codigo, contexto);

    return {
        cep, atributos, campos, spinner, feedback, consultas, temporizadores,
        focouNumero: () => focouNumero,
        digitar: (valor) => { cep.value = valor; cep.dispatchEvent(new Event('input')); },
    };
}

test('clientes: consulta apenas com oito dígitos e preenche o endereço', async () => {
    const form = formulario();
    form.digitar('01001-00');
    assert.equal(form.consultas.length, 0);

    form.digitar('01001-000');
    assert.equal(form.consultas[0].url, 'https://viacep.com.br/ws/01001000/json/');
    assert.equal(form.spinner.hidden, false);
    assert.equal(form.atributos.get('aria-busy'), 'true');
    form.consultas[0].resolve(endereco);
    await concluir();

    assert.deepEqual(Object.values(form.campos).map((campo) => campo.value), ['Praça da Sé', 'Sé', 'São Paulo', 'SP']);
    assert.equal(form.feedback.textContent, 'Endereço preenchido pelo CEP.');
    assert.equal(form.spinner.hidden, true);
    assert.equal(form.atributos.has('aria-busy'), false);
    assert.equal(form.temporizadores.size, 0);
    form.cep.dispatchEvent(new Event('blur'));
    assert.equal(form.consultas.length, 1);
});

test('ordens: mantém o foco no número depois de preencher o endereço', async () => {
    const form = formulario({ numero: true });
    form.cep.value = '01001000';
    form.cep.dispatchEvent(new Event('blur'));
    form.consultas[0].resolve(endereco);
    await concluir();

    assert.equal(form.focouNumero(), true);
    assert.equal(form.campos.uf.value, 'SP');
});

for (const falha of ['inexistente', 'http', 'rede', 'timeout']) {
    test(`CEP com falha ${falha}: preserva o endereço manual e permite tentar novamente`, async () => {
        const form = formulario();
        form.digitar('01001-000');
        if (falha === 'inexistente') form.consultas[0].resolve({ erro: true });
        if (falha === 'http') form.consultas[0].resolve({}, false);
        if (falha === 'rede') form.consultas[0].reject(new Error('Sem conexão'));
        if (falha === 'timeout') [...form.temporizadores.values()][0]();
        await concluir();

        assert.deepEqual(Object.values(form.campos).map((campo) => campo.value), ['Anterior', 'Anterior', 'Anterior', 'Anterior']);
        assert.equal(form.feedback.textContent, 'CEP não encontrado. Preencha o endereço manualmente.');
        assert.equal(form.spinner.hidden, true);
        assert.equal(form.atributos.has('aria-busy'), false);
        form.cep.dispatchEvent(new Event('blur'));
        assert.equal(form.consultas.length, 2);
        form.consultas[1].resolve(endereco);
        await concluir();
        assert.equal(form.campos.endereco.value, 'Praça da Sé');
    });
}

test('trocar o CEP durante a busca cancela a consulta e mantém o resultado mais recente', async () => {
    const form = formulario();
    form.digitar('01001-000');
    form.digitar('20040-020');
    form.consultas[1].resolve({ ...endereco, localidade: 'Rio de Janeiro', uf: 'RJ' });
    await concluir();

    assert.equal(form.consultas[0].signal.aborted, true);
    assert.equal(form.campos.cidade.value, 'Rio de Janeiro');
    assert.equal(form.campos.uf.value, 'RJ');
    assert.equal(form.feedback.textContent, 'Endereço preenchido pelo CEP.');
});

test('apagar parte do CEP durante a busca impede o preenchimento do endereço antigo', async () => {
    const form = formulario();
    form.digitar('01001-000');
    form.digitar('01001-00');
    await concluir();

    assert.equal(form.consultas[0].signal.aborted, true);
    assert.equal(form.campos.endereco.value, 'Anterior');
    assert.equal(form.spinner.hidden, true);
    assert.equal(form.feedback.textContent, '');
});

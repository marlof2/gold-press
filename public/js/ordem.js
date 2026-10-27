(() => {
    const form = document.getElementById('ordem-form');
    if (!form) return;
    const money = new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'});
    const area = new Intl.NumberFormat('pt-BR', {minimumFractionDigits: 4, maximumFractionDigits: 4});
    const value = name => Number(form.elements[name].value) || 0;
    const round = (n, decimals = 2) => Math.sign(n) * Math.round((Math.abs(n) + Number.EPSILON) * 10 ** decimals) / 10 ** decimals;
    function calcular() {
        const total_m2 = round(value('altura') * value('largura'), 4);
        const valor_total_m2 = round(total_m2 * value('valor_produto_m2'));
        const valor_total = round(valor_total_m2 * value('quantidade'));
        const valor_produtos = round(valor_total + value('arredondamento') + value('acerto_valor'));
        const valor_total_final = round(valor_produtos + value('valor_servicos') + value('deslocamento') - value('desconto'));
        document.getElementById('total_m2').textContent = area.format(total_m2) + ' m²';
        for (const [key, amount] of Object.entries({valor_total_m2, valor_total, valor_produtos, valor_total_final, resumo_servicos: value('valor_servicos'), resumo_deslocamento: value('deslocamento'), resumo_desconto: value('desconto')})) {
            document.getElementById(key).textContent = money.format(amount);
        }
        const invalid = valor_produtos < 0 || valor_total_final < 0;
        document.getElementById('calculo-erro').hidden = !invalid;
        form.querySelector('button[type="submit"]').disabled = invalid;
    }
    form.querySelectorAll('[data-calc]').forEach(input => input.addEventListener('input', calcular));
    calcular();
})();

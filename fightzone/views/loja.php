<?php
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
$dinheiro = static function ($valor): string {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
};
$erro = $_SESSION['mensagem_loja'] ?? '';
unset($_SESSION['mensagem_loja']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#151515">
    <title><?= $pagina === 'catalogo' ? 'Equipamentos de boxe' : ucfirst($pagina) ?> | FightZone</title>
    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg">
    <link rel="stylesheet" href="public/assets/css/fightzone.css">
</head>
<body>
<header class="shop-header">
    <a class="wordmark" href="index.php"><span class="brand-mark">FZ</span><span>FIGHT<span class="accent">ZONE</span><small>BOXE • TREINO • PERFORMANCE</small></span></a>
    <nav><a href="index.php">Loja</a><a href="index.php?controller=loja&action=carrinho">Carrinho <span class="cart-count"><?= (int)$carrinhoQuantidade ?></span></a><?php if (!empty($_SESSION['cliente_usuario_id'])): ?><a href="index.php?controller=cliente&action=meusPedidos">Meus pedidos</a><a href="index.php?controller=cliente&action=index">Minha conta</a><?php else: ?><a href="index.php?controller=cliente&action=index">Entrar</a><?php endif; ?></nav>
</header>
<?php if ($erro !== ''): ?><div class="notice container"><?= $h($erro) ?></div><?php endif; ?>

<?php if ($pagina === 'catalogo'): ?>
<main>
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">EQUIPAMENTO CERTO. TREINO SÉRIO.</p>
            <h1>Entre no ringue<br><span>do seu jeito.</span></h1>
            <p>Luvas, proteções e acessórios selecionados para acompanhar sua evolução — da primeira aula ao próximo campeonato.</p>
            <a class="button button-primary" href="#produtos">Ver equipamentos <span>↓</span></a>
        </div>
        <div class="hero-stamp" aria-hidden="true"><span>FIGHT</span><b>YOUR<br>ROUND</b><small>EST. 2026</small></div>
        <div class="hero-bottom"><span>FEITO PARA QUEM TREINA</span><span>01 / 03</span></div>
    </section>
    <section class="benefits container">
        <div><span class="benefit-icon">01</span><p><strong>Escolha sem pressa</strong><small>Equipamentos para todos os níveis</small></p></div>
        <div><span class="benefit-icon">02</span><p><strong>Frete calculado por CEP</strong><small>Estimativa antes de fechar o pedido</small></p></div>
        <div><span class="benefit-icon">03</span><p><strong>Pedido organizado</strong><small>Resumo claro antes de confirmar</small></p></div>
    </section>
    <section class="catalog container" id="produtos">
        <div class="section-heading"><div><p class="eyebrow">A SELEÇÃO FIGHTZONE</p><h2>Encontre seu equipamento</h2></div>
            <form class="search-form" method="get"><input type="hidden" name="controller" value="loja"><input type="hidden" name="action" value="catalogo"><input name="q" value="<?= $h($busca) ?>" placeholder="Buscar equipamento"><button aria-label="Buscar">⌕</button></form>
        </div>
        <?php if (!$produtos): ?><div class="empty-state"><h3><?= $busca !== '' ? 'Não encontramos esse equipamento.' : 'Estamos preparando a vitrine.' ?></h3><p><?= $busca !== '' ? 'Tente outra busca ou veja todos os produtos.' : 'Cadastre os primeiros produtos no painel de estoque.' ?></p><?php if ($busca !== ''): ?><a class="text-link" href="index.php">Ver todos os produtos →</a><?php endif; ?></div>
        <?php else: ?><div class="product-grid">
            <?php foreach ($produtos as $indice => $produto): ?><article class="product-card">
                <a class="product-image product-tone-<?= $indice % 4 ?>" href="#produto-<?= (int)$produto['id_produto'] ?>" id="produto-<?= (int)$produto['id_produto'] ?>">
                    <?php if (!empty($produto['imagem'])): ?><img src="<?= $h($produto['imagem']) ?>" alt="<?= $h($produto['nome']) ?>" loading="lazy"><?php else: ?><span class="product-monogram">FZ<span> / <?= str_pad((string)($indice + 1), 2, '0', STR_PAD_LEFT) ?></span></span><?php endif; ?>
                    <span class="category-tag"><?= $h($produto['categoria_nome'] ?? 'Equipamento') ?></span>
                </a>
                <div class="product-info"><p class="product-sku"><?= $h($produto['sku']) ?></p><h3><?= $h($produto['nome']) ?></h3><p class="product-description"><?= $h($produto['descricao'] ?? '') ?></p>
                    <div class="product-purchase"><strong><?= $dinheiro($produto['preco']) ?></strong><form method="post" action="index.php?controller=loja&action=adicionar"><input type="hidden" name="csrf" value="<?= $h(LojaController::token()) ?>"><input type="hidden" name="produto_id" value="<?= (int)$produto['id_produto'] ?>"><button class="button button-dark" type="submit">Adicionar <span>+</span></button></form></div>
                </div>
            </article><?php endforeach; ?>
        </div><?php endif; ?>
    </section>
    <section class="closing container"><p class="eyebrow">O PRÓXIMO ROUND COMEÇA AQUI</p><h2>Treine com intenção.<br><span>O resto é repetição.</span></h2><a class="text-link" href="#produtos">Escolher equipamento →</a></section>
</main>

<?php elseif ($pagina === 'carrinho'): ?>
<main class="page-shell container">
    <div class="page-title"><p class="eyebrow">SUA SELEÇÃO</p><h1>Carrinho</h1><a class="text-link" href="index.php">← Continuar comprando</a></div>
    <?php if (!$itens): ?><div class="empty-state"><h2>Seu carrinho está vazio.</h2><p>Encontre seu próximo equipamento na loja.</p><a class="button button-primary" href="index.php">Explorar produtos</a></div>
    <?php else: ?><div class="cart-layout"><section>
        <form method="post" action="index.php?controller=loja&action=atualizarCarrinho">
            <input type="hidden" name="csrf" value="<?= $h(LojaController::token()) ?>">
            <?php foreach ($itens as $item): ?><article class="cart-item"><div class="cart-product-mark"><?php if (!empty($item['imagem'])): ?><img src="<?= $h($item['imagem']) ?>" alt="<?= $h($item['nome']) ?>"><?php else: ?>FZ<?php endif; ?></div><div class="cart-item-info"><span class="eyebrow"><?= $h($item['categoria_nome'] ?? $item['sku']) ?></span><h3><?= $h($item['nome']) ?></h3><p><?= $dinheiro($item['preco']) ?> cada</p></div><label class="quantity-control">Qtd.<input type="number" name="quantidade[<?= (int)$item['id_produto'] ?>]" min="0" max="<?= (int)$item['estoque'] ?>" value="<?= (int)$item['quantidade'] ?>"></label><strong><?= $dinheiro($item['total']) ?></strong></article><?php endforeach; ?>
            <button class="button button-outline" type="submit">Atualizar carrinho</button><small class="hint">Use quantidade zero para remover um item.</small>
        </form>
    </section><aside class="summary-card"><h2>Resumo do pedido</h2><div class="summary-line"><span>Subtotal</span><strong><?= $dinheiro($subtotal) ?></strong></div><form class="freight-form" method="post" action="index.php?controller=loja&action=frete"><input type="hidden" name="csrf" value="<?= $h(LojaController::token()) ?>"><label for="cep-frete">Calcular estimativa de frete</label><div><input id="cep-frete" name="cep" inputmode="numeric" maxlength="9" placeholder="00000-000" value="<?= $h($cep) ?>" required><button type="submit">Calcular</button></div></form>
        <div class="summary-line"><span>Frete estimado</span><strong><?= $frete === null ? 'Calcule pelo CEP' : $dinheiro($frete) ?></strong></div>
        <div class="summary-total"><span>Total estimado</span><strong><?= $dinheiro($subtotal + (float)($frete ?? 0)) ?></strong></div>
        <?php if ($frete !== null): ?><a class="button button-primary button-full" href="index.php?controller=loja&action=checkout">Continuar para entrega</a><?php endif; ?><p class="fine-print">Valor de frete ilustrativo; sujeito à confirmação pela transportadora.</p>
    </aside></div><?php endif; ?>
</main>

<?php elseif ($pagina === 'checkout'): ?>
<main class="page-shell container">
    <div class="page-title"><p class="eyebrow">ÚLTIMO PASSO</p><h1>Entrega e pagamento</h1><a class="text-link" href="index.php?controller=loja&action=carrinho">← Voltar ao carrinho</a></div>
    <div class="checkout-layout"><form class="checkout-form" method="post" action="index.php?controller=loja&action=checkout">
        <input type="hidden" name="csrf" value="<?= $h(LojaController::token()) ?>">
        <h2>Seus dados</h2><div class="form-grid">
            <label class="wide">Nome completo<input name="nome" autocomplete="name" required maxlength="160" value="<?= $h($perfilCliente['nome']) ?>" readonly></label>
            <label>E-mail<input name="email" type="email" autocomplete="email" required value="<?= $h($perfilCliente['email']) ?>" readonly></label><label>CPF/CNPJ <span>(opcional)</span><input name="documento" inputmode="numeric" value="<?= $h($perfilCliente['cpf'] ?? '') ?>"></label>
            <label>Telefone<input name="telefone" autocomplete="tel" type="tel" value="<?= $h($perfilCliente['telefone'] ?? '') ?>"></label>
        </div>
        <h2>Endereço de entrega</h2><div class="form-grid">
            <label>CEP<input id="cep" name="cep" inputmode="numeric" maxlength="9" autocomplete="postal-code" required value="<?= $h($cep) ?>"><small id="cep-status" class="field-status">O preenchimento pelo ViaCEP é opcional; confira o endereço.</small></label><label class="wide">Rua / Avenida<input id="endereco" name="endereco" autocomplete="street-address" required value="<?= $h($perfilCliente['logradouro'] ?? '') ?>"></label>
            <label>Número<input name="numero" required value="<?= $h($perfilCliente['numero'] ?? '') ?>"></label><label>Complemento<input name="complemento" value="<?= $h($perfilCliente['complemento'] ?? '') ?>"></label>
            <label>Cidade<input id="cidade" name="cidade" autocomplete="address-level2" required value="<?= $h($perfilCliente['cidade'] ?? '') ?>"></label><label>UF<input id="uf" name="uf" maxlength="2" autocomplete="address-level1" required value="<?= $h($perfilCliente['uf'] ?? '') ?>"></label>
        </div>
        <h2>Forma de pagamento</h2><p class="checkout-note">Escolha como deseja pagar. O pedido será registrado e ficará aguardando a confirmação do pagamento. Não informe dados de cartão nesta página.</p>
        <label class="payment-option"><input type="radio" name="forma_pagamento" value="pix" required><span><strong>PIX</strong><small>Pagamento instantâneo</small></span></label>
        <section class="pix-details" id="pix-details" hidden aria-live="polite">
            <h3>Pagamento via PIX</h3>
            <?php if ($pixKey !== ''): ?>
                <p>Use a chave abaixo no aplicativo do seu banco. Antes de confirmar, confira se o destinatário é o titular da chave.</p>
                <div class="pix-key-row"><code id="pix-key"><?= $h($pixKey) ?></code><button class="button button-outline" id="copy-pix-key" type="button">Copiar chave</button></div>
                <p class="fine-print">Demonstração: o sistema não gera cobrança PIX nem confirma pagamentos automaticamente. Confira o valor e o destinatário no banco antes de pagar.</p>
            <?php else: ?>
                <p>A chave PIX ainda não foi configurada. O responsável pelo projeto deve cadastrá-la localmente em <code>config/pagamento.local.php</code>.</p>
            <?php endif; ?>
        </section>
        <label class="payment-option"><input type="radio" name="forma_pagamento" value="boleto"><span><strong>Boleto bancário</strong><small>Pagamento por boleto</small></span></label>
        <label class="payment-option"><input type="radio" name="forma_pagamento" value="cartao"><span><strong>Cartão</strong><small>Pagamento com cartão</small></span></label>
        <button class="button button-primary button-full" type="submit">Finalizar pedido</button>
    </form><aside class="summary-card checkout-summary"><h2>Seu pedido</h2><?php foreach ($itens as $item): ?><div class="summary-line"><span><?= (int)$item['quantidade'] ?> × <?= $h($item['nome']) ?></span><strong><?= $dinheiro($item['total']) ?></strong></div><?php endforeach; ?><div class="summary-line"><span>Subtotal</span><strong><?= $dinheiro($subtotal) ?></strong></div>    <div class="summary-line"><span>Frete estimado</span><strong id="checkout-frete-value"><?= $frete === null ? 'Informe o CEP' : $dinheiro($frete) ?></strong></div><div class="summary-total"><span>Total</span><strong id="checkout-total"><?= $dinheiro($subtotal + (float)($frete ?? 0)) ?></strong></div><p class="fine-print">Frete estimado pelo CEP, peso e volume; não é cotação oficial.</p></aside></div>
</main>
<script>
const subtotalCheckout = <?= json_encode($subtotal) ?>;
const pesoCubadoEncomenda = <?= json_encode(array_reduce($itens, static function ($peso, $item) {
    return $peso + max((float)$item['peso'], (float)$item['altura'] * (float)$item['largura'] * (float)$item['comprimento'] / 6000) * (int)$item['quantidade'];
}, 0.0)) ?>;
const pixDetails = document.getElementById('pix-details');
document.querySelectorAll('input[name="forma_pagamento"]').forEach(function (opcao) {
    opcao.addEventListener('change', function () {
        pixDetails.hidden = this.value !== 'pix';
    });
});
const copyPixButton = document.getElementById('copy-pix-key');
if (copyPixButton) {
    copyPixButton.addEventListener('click', async function () {
        const chave = document.getElementById('pix-key').textContent.trim();
        try {
            await navigator.clipboard.writeText(chave);
            this.textContent = 'Chave copiada';
        } catch (erro) {
            this.textContent = 'Copie a chave acima';
        }
    });
}
const cepCheckout = document.getElementById('cep');
cepCheckout.addEventListener('input', function () {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length !== 8) return;
    const prefixo = Number(cep.slice(0, 2));
    const faixas = [[0,19,16.90],[20,39,19.90],[40,65,26.90],[66,69,34.90],[70,79,24.90],[80,99,21.90]];
    const faixa = faixas.find(item => prefixo >= item[0] && prefixo <= item[1]);
    const frete = (faixa ? faixa[2] : 24.90) + Math.max(0, pesoCubadoEncomenda - 1) * 3.5;
    const formatado = new Intl.NumberFormat('pt-BR', {style:'currency',currency:'BRL'}).format(frete);
    document.getElementById('checkout-frete-value').textContent = formatado;
    document.getElementById('checkout-total').textContent = new Intl.NumberFormat('pt-BR', {style:'currency',currency:'BRL'}).format(subtotalCheckout + frete);
});
cepCheckout.addEventListener('blur', async function () {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length !== 8) return;
    try {
    const resposta = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
    if (!resposta.ok) throw new Error('Falha ao consultar CEP');
    const dados = await resposta.json();
    if (dados.erro) throw new Error('CEP não encontrado');
    document.getElementById('endereco').value = dados.logradouro || '';
    document.getElementById('cidade').value = dados.localidade || '';
    document.getElementById('uf').value = dados.uf || '';
    document.getElementById('cep-status').textContent = 'Endereço preenchido pelo ViaCEP. Confira antes de concluir.';
    } catch (erro) {
    document.getElementById('cep-status').textContent = 'ViaCEP indisponível ou CEP não localizado. Preencha o endereço manualmente.';
    }
});
</script>

<?php else: ?>
<main class="page-shell container confirmation">
    <div class="confirmation-mark">✓</div><p class="eyebrow">PEDIDO RECEBIDO</p><h1>Valeu pelo pedido, <?= $h($pedido['cliente_nome']) ?>.</h1>
    <p>Seu pedido <strong>#<?= (int)$pedido['id_pedido'] ?></strong> foi recebido e aguarda a confirmação do pagamento.</p>
    <div class="confirmation-total"><span>Total do pedido</span><strong><?= $dinheiro($pedido['total']) ?></strong></div>
    <?php if ($pedido['forma_pagamento'] === 'pix'): ?><section class="pix-details confirmation-pix"><h2>Pagamento via PIX</h2>
        <?php if ($pixKey !== ''): ?><p>Use esta chave no aplicativo do seu banco e confira o destinatário antes de confirmar:</p><div class="pix-key-row"><code><?= $h($pixKey) ?></code></div>
        <?php else: ?><p>A chave PIX não está configurada para este projeto.</p><?php endif; ?>
        <p class="fine-print">O pedido permanece aguardando confirmação. Este projeto não confirma pagamentos automaticamente.</p>
    </section><?php endif; ?>
    <a class="button button-primary" href="index.php?controller=loja&action=notaFiscal&id=<?= (int)$pedido['id_pedido'] ?>&token=<?= $h($pedido['codigo_acesso']) ?>">Baixar comprovante em PDF</a>
    <a class="button button-outline" href="index.php?controller=cliente&action=meusPedidos">Acompanhar em Meus pedidos</a>
    <p class="fine-print">O PDF contém o resumo do pedido e não substitui uma nota fiscal eletrônica.</p><a class="text-link" href="index.php">Voltar à loja →</a>
</main>
<?php endif; ?>

<footer class="shop-footer"><a class="wordmark" href="index.php"><span class="brand-mark">FZ</span><span>FIGHT<span class="accent">ZONE</span></span></a><p>Equipamento bom não luta por você. Mas ajuda.</p><span>© FightZone • Todos os direitos reservados</span></footer>
</body>
</html>

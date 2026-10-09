<?php
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
$statusLabels = [
    'aguardando' => 'Aguardando pagamento',
    'pago' => 'Pagamento confirmado',
    'separacao' => 'Em preparação',
    'enviado' => 'Enviado',
    'entregue' => 'Entregue',
    'cancelado' => 'Cancelado'
];
$formaPagamento = ['pix' => 'PIX', 'cartao' => 'Cartão', 'boleto' => 'Boleto bancário'];
$mensagem = $_SESSION['mensagem_pedidos'] ?? '';
unset($_SESSION['mensagem_pedidos']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#151515">
    <title>Meus pedidos | FightZone</title>
    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg">
    <link rel="stylesheet" href="public/assets/css/fightzone.css?v=orders-3">
</head>
<body>
<header class="shop-header">
    <a class="wordmark" href="index.php"><span class="brand-mark">FZ</span><span>FIGHT<span class="accent">ZONE</span><small>BOXE • TREINO • PERFORMANCE</small></span></a>
    <nav><a href="index.php">Loja</a><a href="index.php?controller=cliente&action=meusPedidos" aria-current="page">Meus pedidos</a><a href="index.php?controller=cliente&action=index">Minha conta</a><a href="index.php?controller=cliente&action=sair">Sair</a></nav>
</header>
<main class="page-shell container customer-orders">
    <div class="page-title">
        <p class="eyebrow">SUA CONTA FIGHTZONE</p>
        <h1>Meus pedidos</h1>
        <p>Acompanhe o pagamento, a preparação e o envio dos seus pedidos.</p>
    </div>
    <?php if ($mensagem !== ''): ?><div class="notice orders-notice"><?= $h($mensagem) ?></div><?php endif; ?>
    <?php if (!$pedidos): ?>
        <section class="orders-empty">
            <h2>Você ainda não fez pedidos.</h2>
            <p>Quando concluir uma compra, o status e os detalhes aparecerão aqui.</p>
            <a class="button button-primary" href="index.php">Explorar a loja</a>
        </section>
    <?php else: ?>
        <p class="orders-note">A previsão de entrega é estimada e pode variar conforme o transporte.</p>
        <div class="customer-order-list">
            <?php foreach ($pedidos as $pedido):
                $status = $pedido['status'];
                $cancelado = $status === 'cancelado';
            ?>
                <article class="customer-order-card">
                    <header class="customer-order-heading">
                        <div>
                            <p class="order-number">Pedido #<?= (int)$pedido['id_pedido'] ?></p>
                            <p class="order-date">Realizado em <?= $h(date('d/m/Y \à\s H:i', strtotime($pedido['criado_em']))) ?></p>
                        </div>
                        <span class="order-status order-status-<?= $h($status) ?>"><?= $h($statusLabels[$status] ?? $status) ?></span>
                    </header>

                    <div class="customer-order-content">
                        <div class="customer-order-main">
                            <section class="customer-order-items">
                                <h3>Itens comprados</h3>
                                <ul class="order-items">
                                <?php foreach ($pedido['itens'] as $item): ?>
                                    <li>
                                        <span class="order-item-quantity"><?= (int)$item['quantidade'] ?> ×</span>
                                        <span class="order-item-name"><?= $h($item['produto_nome']) ?></span>
                                        <strong>R$ <?= number_format((float)$item['preco_unitario'] * (int)$item['quantidade'], 2, ',', '.') ?></strong>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </section>
                            <section class="customer-order-address">
                                <h3>Endereço de entrega</h3>
                                <p><?= $h($pedido['endereco']) ?></p>
                                <p>CEP <?= $h($pedido['cep']) ?></p>
                            </section>
                        </div>
                        <aside class="customer-order-summary">
                            <p class="summary-label">Total do pedido</p>
                            <strong class="summary-amount">R$ <?= number_format((float)$pedido['total'], 2, ',', '.') ?></strong>
                            <div class="order-summary-line"><span>Produtos</span><strong>R$ <?= number_format((float)$pedido['subtotal'], 2, ',', '.') ?></strong></div>
                            <div class="order-summary-line"><span>Frete</span><strong>R$ <?= number_format((float)$pedido['frete'], 2, ',', '.') ?></strong></div>
                            <div class="order-payment">
                                <span>Pagamento</span>
                                <strong><?= $h($formaPagamento[$pedido['forma_pagamento']] ?? $pedido['forma_pagamento']) ?></strong>
                                <small><?= $h(ucfirst($pedido['status_pagamento'] ?: 'pendente')) ?></small>
                            </div>
                            <div class="order-delivery">
                                <span>Previsão de entrega</span>
                                <strong><?php if ($cancelado): ?>Indisponível<?php elseif ($status === 'entregue'): ?>Entregue<?php else: ?><?= $h($pedido['previsao_entrega']) ?><?php endif; ?></strong>
                            </div>
                        </aside>
                    </div>

                    <footer class="customer-order-footer">
                        <a class="button button-outline" href="index.php?controller=loja&action=notaFiscal&id=<?= (int)$pedido['id_pedido'] ?>&amp;token=<?= $h($pedido['codigo_acesso']) ?>">Baixar resumo do pedido</a>
                        <?php if ($status === 'aguardando' && $pedido['status_pagamento'] === 'pendente'): ?>
                            <form class="cancel-order-form" method="post" action="index.php?controller=cliente&action=cancelarPedido">
                                <input type="hidden" name="csrf" value="<?= $h(ClienteController::token()) ?>">
                                <input type="hidden" name="pedido_id" value="<?= (int)$pedido['id_pedido'] ?>">
                                <button class="button button-cancel" type="submit">Cancelar pedido</button>
                            </form>
                        <?php endif; ?>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<dialog class="cancel-order-dialog" id="cancel-order-dialog" aria-labelledby="cancel-order-title" aria-describedby="cancel-order-description">
    <div class="cancel-order-dialog-mark" aria-hidden="true">!</div>
    <h2 id="cancel-order-title">Cancelar pedido?</h2>
    <p id="cancel-order-description">Tem certeza de que deseja cancelar este pedido? Os itens voltarão ao estoque. Esta ação não pode ser desfeita.</p>
    <div class="cancel-order-dialog-actions">
        <button class="button button-outline" id="keep-order" type="button">Não, voltar</button>
        <button class="button button-cancel-confirm" id="confirm-cancel-order" type="button">Sim, cancelar pedido</button>
    </div>
</dialog>
<footer class="shop-footer"><a class="wordmark" href="index.php"><span class="brand-mark">FZ</span><span>FIGHT<span class="accent">ZONE</span></span></a><p>Equipamento bom não luta por você. Mas ajuda.</p><span>© FightZone • Todos os direitos reservados</span></footer>
<script>
const cancelDialog = document.getElementById('cancel-order-dialog');
let cancelOrderForm = null;

document.querySelectorAll('.cancel-order-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        cancelOrderForm = this;
        cancelDialog.showModal();
    });
});

document.getElementById('keep-order').addEventListener('click', function () {
    cancelDialog.close();
});

document.getElementById('confirm-cancel-order').addEventListener('click', function () {
    if (!cancelOrderForm) return;
    this.disabled = true;
    cancelOrderForm.submit();
});

cancelDialog.addEventListener('close', function () {
    cancelOrderForm = null;
    document.getElementById('confirm-cancel-order').disabled = false;
});
</script>
</body>
</html>

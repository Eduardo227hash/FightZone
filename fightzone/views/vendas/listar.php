<?php
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pedidos | FightZone</title><link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg"><link rel="stylesheet" href="public/assets/css/produtos.css"></head>
<body>
<header class="header"><div class="container header-inner"><a class="brand" href="index.php?controller=auth&action=dashboard"><span class="brand-mark">FZ</span> FIGHTZONE <span class="badge">PEDIDOS</span></a><div class="user"><a class="btn" href="index.php?controller=auth&action=dashboard">Painel</a><a class="btn btn-ghost" href="index.php?controller=auth&action=logout">Sair</a></div></div></header>
<main class="container" style="padding-top:35px"><section class="card"><p class="eyebrow">OPERAÇÃO</p><h2>Pedidos da loja</h2><div class="table-wrap"><table class="table"><thead><tr><th>Pedido</th><th>Data</th><th>Cliente</th><th>E-mail</th><th>Pagamento</th><th>Status</th><th>Total</th></tr></thead><tbody>
<?php foreach ($vendas as $venda): ?><tr><td>#<?= (int)$venda['id_pedido'] ?></td><td><?= $h(date('d/m/Y H:i', strtotime($venda['criado_em']))) ?></td><td><?= $h($venda['cliente_nome']) ?></td><td><?= $h($venda['cliente_email']) ?></td><td><?= $h(strtoupper(str_replace('_', ' ', $venda['forma_pagamento']))) ?></td><td><?= $h($venda['status_pedido']) ?></td><td>R$ <?= number_format((float)$venda['total'], 2, ',', '.') ?></td></tr><?php endforeach; ?>
<?php if (!$vendas): ?><tr><td colspan="7">Nenhum pedido registrado ainda.</td></tr><?php endif; ?>
</tbody></table></div></section></main>
</body></html>

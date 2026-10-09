<?php
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
$receita = array_sum(array_map(static function ($venda) {
    return (float)$venda['total'];
}, $vendas));
$estoqueUnidades = array_sum(array_map(static function ($produto) {
    return (int)$produto['estoque'];
}, $produtos));
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Relatórios | FightZone</title><link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg"><link rel="stylesheet" href="public/assets/css/produtos.css"></head>
<body>
<header class="header"><div class="container header-inner"><a class="brand" href="index.php?controller=auth&action=dashboard"><span class="brand-mark">FZ</span> FIGHTZONE <span class="badge">RELATÓRIOS</span></a><a class="btn" href="index.php?controller=auth&action=dashboard">Voltar ao painel</a></div></header>
<main class="container" style="padding:35px 0">
    <section class="card"><p class="eyebrow">VISÃO DO NEGÓCIO</p><h2>Resumo do período</h2>
        <form method="get" class="report-filter"><input type="hidden" name="controller" value="relatorio"><input type="hidden" name="action" value="index"><label>De <input class="input" type="date" name="inicio" value="<?= $h($inicio) ?>"></label><label>Até <input class="input" type="date" name="fim" value="<?= $h($fim) ?>"></label><button class="btn btn-primary" type="submit">Aplicar</button></form>
        <div class="report-metrics"><div><small>Pedidos no período</small><strong><?= count($vendas) ?></strong></div><div><small>Valor total dos pedidos</small><strong>R$ <?= number_format($receita, 2, ',', '.') ?></strong></div><div><small>Produtos ativos no catálogo</small><strong><?= count(array_filter($produtos, static function ($produto) { return (int)$produto['ativo'] === 1; })) ?></strong></div><div><small>Unidades em estoque</small><strong><?= $estoqueUnidades ?></strong></div></div>
    </section>
    <section class="card report-table"><h2>Pedidos do período</h2><div class="table-wrap"><table class="table"><thead><tr><th>Pedido</th><th>Data</th><th>Cliente</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach ($vendas as $venda): ?><tr><td>#<?= (int)$venda['id_pedido'] ?></td><td><?= $h(date('d/m/Y H:i', strtotime($venda['criado_em']))) ?></td><td><?= $h($venda['cliente_nome']) ?></td><td>R$ <?= number_format((float)$venda['total'], 2, ',', '.') ?></td><td><?= $h($venda['status_pedido']) ?></td></tr><?php endforeach; ?><?php if (!$vendas): ?><tr><td colspan="5">Sem pedidos para esse período.</td></tr><?php endif; ?></tbody></table></div></section>
</main>
</body></html>

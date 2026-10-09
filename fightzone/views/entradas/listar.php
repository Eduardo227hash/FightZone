<?php
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Movimentações de estoque | FightZone</title><link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg"><link rel="stylesheet" href="public/assets/css/produtos.css"></head>
<body>
<header class="header"><div class="container header-inner"><a class="brand" href="index.php?controller=auth&action=dashboard"><span class="brand-mark">FZ</span> FIGHTZONE <span class="badge">ESTOQUE</span></a><a class="btn" href="index.php?controller=auth&action=dashboard">Voltar ao painel</a></div></header>
<main class="container" style="padding:35px 0"><section class="card"><p class="eyebrow">HISTÓRICO</p><h2>Movimentações de estoque</h2>
<?php if (isset($_GET['sucesso'])): ?><p class="success-note">Entrada registrada e estoque atualizado.</p><?php endif; ?>
<div class="actions"><a class="btn btn-primary" href="index.php?controller=entrada&action=criar">Registrar entrada</a></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Data</th><th>Produto</th><th>Movimento</th><th>Quantidade</th><th>Responsável</th><th>Observação</th></tr></thead><tbody>
<?php foreach ($entradas as $entrada): ?><tr><td><?= $h(date('d/m/Y H:i', strtotime($entrada['created_at']))) ?></td><td><?= $h($entrada['nome_produto']) ?></td><td><?= $h(ucfirst($entrada['tipo'])) ?></td><td><?= (int)$entrada['quantidade'] ?></td><td><?= $h($entrada['nome_usuario'] ?? 'Loja') ?></td><td><?= $h($entrada['observacao'] ?? '') ?></td></tr><?php endforeach; ?>
<?php if (!$entradas): ?><tr><td colspan="6">Nenhuma movimentação registrada.</td></tr><?php endif; ?>
</tbody></table></div></section></main>
</body></html>

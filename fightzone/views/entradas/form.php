<?php
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Registrar entrada | FightZone</title><link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg"><link rel="stylesheet" href="public/assets/css/produtos.css"></head>
<body>
<header class="header"><div class="container header-inner"><a class="brand" href="index.php?controller=auth&action=dashboard"><span class="brand-mark">FZ</span> FIGHTZONE <span class="badge">ESTOQUE</span></a><a class="btn" href="index.php?controller=entrada&action=index">Movimentações</a></div></header>
<main class="container" style="padding:35px 0"><section class="card" style="max-width:620px;margin:auto"><p class="eyebrow">REPOSIÇÃO</p><h2>Registrar entrada no estoque</h2>
<form method="post" action="index.php?controller=entrada&action=store">
    <input type="hidden" name="csrf" value="<?= $h($csrfEstoque) ?>">
    <div class="form-group"><label for="produto">Produto</label><select class="input" id="produto" name="produto_id" required><option value="">Selecione um produto</option><?php foreach ($produtos as $produto): ?><option value="<?= (int)$produto['id'] ?>"><?= $h($produto['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="quantidade">Quantidade recebida</label><input class="input" id="quantidade" type="number" name="quantidade" min="1" step="1" required></div>
    <div class="form-group"><label for="observacao">Observação</label><input class="input" id="observacao" name="observacao" maxlength="255" placeholder="Ex.: reposição do fornecedor"></div>
    <div class="actions"><button class="btn btn-primary" type="submit">Registrar entrada</button><a class="btn" href="index.php?controller=entrada&action=index">Cancelar</a></div>
</form></section></main>
</body></html>

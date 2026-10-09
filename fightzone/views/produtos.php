<?php
$editar = $editar ?? null;
$produtos = $produtos ?? [];
$categorias = $categorias ?? [];
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estoque | FightZone</title>
    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg">
    <link rel="stylesheet" href="public/assets/css/produtos.css?v=product-images">
</head>
<body>
<header class="header">
    <div class="container header-inner">
        <a class="brand" href="index.php?controller=auth&action=dashboard"><span class="brand-mark">FZ</span> FIGHTZONE <span class="badge">PAINEL</span></a>
        <div class="user">Olá, <strong><?= $h($_SESSION['nome'] ?? 'Equipe') ?></strong><a class="btn btn-ghost" href="index.php?controller=auth&action=logout">Sair</a></div>
    </div>
</header>
<main class="container grid">
    <section class="card">
        <p class="eyebrow">ESTOQUE</p>
        <h2><?= $editar ? 'Editar produto' : 'Novo produto' ?></h2>
        <form method="post" action="index.php?controller=produto&action=salvar" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id" value="<?= (int)($editar['id_produto'] ?? 0) ?>">
            <input type="hidden" name="csrf" value="<?= $h($csrfEstoque) ?>">
            <div class="form-group"><label for="nome">Nome do produto</label><input class="input" id="nome" name="nome_produto" autocomplete="off" list="produtos-cadastrados" required maxlength="140" value="<?= $h($editar['nome'] ?? '') ?>"><datalist id="produtos-cadastrados"><?php foreach ($produtos as $produto): ?><option value="<?= $h($produto['nome']) ?>"><?php endforeach; ?></datalist></div>
            <div class="form-group"><label for="descricao">Descrição</label><textarea class="input" id="descricao" name="descricao" rows="3"><?= $h($editar['descricao'] ?? '') ?></textarea></div>
            <div class="form-group"><label for="categoria">Categoria</label><select class="input" id="categoria" name="categoria_id" required><option value="">Selecione</option><?php foreach ($categorias as $categoria): ?><option value="<?= (int)$categoria['id'] ?>" <?= (int)($editar['categoria_id'] ?? 0) === (int)$categoria['id'] ? 'selected' : '' ?>><?= $h($categoria['nome']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="sku">SKU</label><input class="input" id="sku" value="<?= $h($editar['sku'] ?? '') ?>" placeholder="<?= $editar ? '' : 'Gerado ao salvar' ?>" readonly><small>Gerado automaticamente no padrão FZ-000001.</small></div>
            <div class="form-group"><label for="marca">Marca</label><input class="input" id="marca" name="marca" required maxlength="80" value="<?= $h($editar['marca'] ?? 'FightZone') ?>"></div>
            <div class="form-row">
                <div class="form-group"><label for="preco">Preço (R$)</label><input class="input" id="preco" type="number" name="preco" min="0.01" step="0.01" required value="<?= $h($editar['preco'] ?? '') ?>"></div>
                <div class="form-group"><label for="estoque">Unidades em estoque</label><input class="input" id="estoque" type="number" name="estoque" min="0" step="1" required value="<?= $h($editar['estoque'] ?? 0) ?>"></div>
            </div>
            <div class="form-group"><label for="peso">Peso unitário (kg)</label><input class="input" id="peso" type="number" name="peso" min="0.1" step="0.1" required value="<?= $h($editar['peso'] ?? '0.5') ?>"></div>
            <div class="form-row"><div class="form-group"><label for="altura">Altura (cm)</label><input class="input" id="altura" type="number" name="altura" min="1" step="1" required value="<?= $h($editar['altura'] ?? 10) ?>"></div><div class="form-group"><label for="largura">Largura (cm)</label><input class="input" id="largura" type="number" name="largura" min="1" step="1" required value="<?= $h($editar['largura'] ?? 10) ?>"></div></div>
            <div class="form-group"><label for="comprimento">Comprimento (cm)</label><input class="input" id="comprimento" type="number" name="comprimento" min="1" step="1" required value="<?= $h($editar['comprimento'] ?? 10) ?>"></div>
            <div class="form-group"><label for="imagem">Foto (JPG, PNG ou WebP; até 5 MB)</label><input class="input" id="imagem" type="file" name="imagem" accept="image/jpeg,image/png,image/webp"><?php if (!empty($editar['imagem'])): ?><img class="product-thumb" src="<?= $h($editar['imagem']) ?>" alt="Foto atual do produto"><?php endif; ?></div>
            <div class="actions"><button class="btn btn-primary" type="submit">Salvar produto</button><a class="btn" href="index.php?controller=produto&action=index">Limpar</a></div>
        </form>
    </section>
    <section class="card inventory">
        <p class="eyebrow">CATÁLOGO</p>
        <h2>Produtos cadastrados</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Foto</th><th>Produto</th><th>Categoria</th><th>SKU</th><th>Estoque</th><th>Preço</th><th>Ações</th></tr></thead>
            <tbody><?php foreach ($produtos as $produto): ?><tr>
                <td><?php if (!empty($produto['imagem'])): ?><img class="catalog-product-thumb" src="<?= $h($produto['imagem']) ?>" alt="Foto de <?= $h($produto['nome']) ?>" loading="lazy"><?php else: ?><span class="catalog-product-placeholder" aria-label="Sem foto para <?= $h($produto['nome']) ?>">FZ</span><?php endif; ?></td>
                <td><?= $h($produto['nome']) ?></td>
                <td><?= $h($produto['categoria_nome'] ?? '—') ?></td>
                <td><?= $h($produto['sku']) ?></td>
                <td><span class="<?= (int)$produto['estoque'] <= 5 ? 'stock-low' : '' ?>"><?= (int)$produto['estoque'] ?></span></td>
                <td>R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?></td>
                <td class="row-actions"><a class="btn" href="index.php?controller=produto&action=index&id=<?= (int)$produto['id_produto'] ?>">Editar</a>
                    <?php if ((int)$produto['ativo']): ?><form class="inline-form" method="post" action="index.php?controller=produto&action=deletar" onsubmit="return confirm('Remover este produto da loja?')"><input type="hidden" name="csrf" value="<?= $h($csrfEstoque) ?>"><input type="hidden" name="id" value="<?= (int)$produto['id_produto'] ?>"><button class="btn btn-danger" type="submit">Remover</button></form><?php else: ?><span class="muted">Inativo</span><?php endif; ?>
                </td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
</main>
</body>
</html>

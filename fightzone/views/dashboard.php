<?php
$nome = $_SESSION['nome'] ?? 'Equipe';
$perfil = $_SESSION['perfil'] ?? 'vendedor';
$metricas = $metricas ?? [
    'vendas' => 0,
    'pedidos' => 0,
    'pedidos_mes' => 0,
    'aguardando' => 0,
    'estoque_baixo' => 0,
    'produtos' => 0
];
$nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
$perfilSeguro = htmlspecialchars($perfil, ENT_QUOTES, 'UTF-8');
$horario = (int)date('G');
$saudacao = $horario < 12 ? 'Bom dia' : ($horario < 18 ? 'Boa tarde' : 'Boa noite');
$somenteAdmin = $perfil === 'admin';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel | FightZone</title>
    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg">
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
</head>
<body>
    <aside class="sidebar">
        <a class="sidebar-brand" href="index.php?controller=auth&action=dashboard">
            <span class="brand-mark">FZ</span>
            <span>FIGHT<span>ZONE</span><small>PAINEL DE GESTÃO</small></span>
        </a>
        <p class="sidebar-label">MENU PRINCIPAL</p>
        <nav class="sidebar-nav">
            <a class="active" href="index.php?controller=auth&action=dashboard"><span>▦</span> Visão geral</a>
            <?php if ($somenteAdmin): ?>
                <a href="index.php?controller=produto&action=index"><span>◈</span> Produtos e estoque</a>
                <a href="index.php?controller=entrada&action=index"><span>↗</span> Entradas de estoque</a>
                <a href="index.php?controller=categoria&action=index"><span>☷</span> Categorias</a>
            <?php endif; ?>
            <a href="index.php?controller=venda&action=index"><span>▤</span> Pedidos</a>
            <a href="index.php?controller=relatorio&action=index"><span>▥</span> Relatórios</a>
        </nav>
        <div class="sidebar-bottom">
            <a class="store-link" href="index.php"><span>↗</span> Abrir loja</a>
            <div class="sidebar-user">
                <span class="avatar"><?= strtoupper(substr($nome, 0, 1)) ?></span>
                <span class="user-detail"><strong><?= $nomeSeguro ?></strong><small><?= $perfilSeguro ?></small></span>
                <a class="logout" href="index.php?controller=auth&action=logout" aria-label="Sair">↪</a>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="page-top">
            <div><p class="eyebrow">FIGHTZONE / PAINEL</p><h1>Visão geral</h1></div>
            <a class="top-store-link" href="index.php">Ver loja <span>↗</span></a>
        </header>
        <section class="welcome-card">
            <div class="welcome-copy">
                <span class="welcome-date"><?= date('d/m/Y') ?> <i></i> <?= $perfilSeguro === 'admin' ? 'ADMINISTRADOR' : 'EQUIPE' ?></span>
                <h2><?= $saudacao ?>, <?= $nomeSeguro ?>.</h2>
                <p>Acompanhe os pedidos e mantenha seu estoque em dia.</p>
                <a href="<?= $somenteAdmin ? 'index.php?controller=produto&action=index' : 'index.php?controller=venda&action=index' ?>" class="welcome-action"><?= $somenteAdmin ? 'Gerenciar produtos' : 'Consultar pedidos' ?><span>→</span></a>
            </div>
            <div class="welcome-art" aria-hidden="true"><span>FZ</span><b>YOUR<br>ROUND.</b><i></i></div>
            <span class="welcome-index">01 <span>/</span> 04</span>
        </section>

        <section class="section-heading">
            <div><p class="eyebrow">ACOMPANHAMENTO</p><h2>Resumo da operação</h2></div>
            <a href="index.php?controller=relatorio&action=index">Ver relatórios <span>→</span></a>
        </section>
        <section class="metrics-grid">
            <article class="metric-card metric-highlight">
                <div class="metric-top"><span>Pedidos no mês</span><i>▤</i></div>
                <strong><?= (int)$metricas['pedidos_mes'] ?></strong>
                <small>Pedidos recebidos neste mês</small>
            </article>
            <article class="metric-card">
                <div class="metric-top"><span>Faturamento confirmado</span><i>R$</i></div>
                <strong>R$ <?= number_format((float)$metricas['vendas'], 2, ',', '.') ?></strong>
                <small>Pedidos com pagamento confirmado</small>
            </article>
            <article class="metric-card">
                <div class="metric-top"><span>Aguardando pagamento</span><i>◷</i></div>
                <strong><?= (int)$metricas['aguardando'] ?></strong>
                <small>Pedidos que precisam de confirmação</small>
            </article>
            <article class="metric-card">
                <div class="metric-top"><span>Estoque baixo</span><i>!</i></div>
                <strong><?= (int)$metricas['estoque_baixo'] ?></strong>
                <small>Produtos com 5 unidades ou menos</small>
            </article>
        </section>

        <section class="bottom-grid">
            <article class="inventory-card">
                <div class="section-heading compact"><div><p class="eyebrow">CATÁLOGO</p><h2>Estoque da loja</h2></div>
                    <?php if ($somenteAdmin): ?><a href="index.php?controller=produto&action=index">Gerenciar <span>→</span></a><?php endif; ?>
                </div>
                <div class="inventory-summary"><span class="inventory-icon">◈</span><div><strong><?= (int)$metricas['produtos'] ?></strong><small>produtos ativos no catálogo</small></div><span class="inventory-status"><i></i> Atualizado</span></div>
                <div class="inventory-bar"><span style="width:<?= min(100, (int)$metricas['produtos'] * 5) ?>%"></span></div>
                <p class="inventory-footnote">Consulte a área de estoque para ver quantidades por produto.</p>
            </article>
            <article class="quick-card">
                <p class="eyebrow">ACESSO RÁPIDO</p><h2>O que você precisa fazer?</h2>
                <?php if ($somenteAdmin): ?>
                    <a href="index.php?controller=produto&action=index"><span class="quick-icon">＋</span><span><strong>Cadastrar produto</strong><small>Adicione um item ao catálogo</small></span><b>→</b></a>
                    <a href="index.php?controller=entrada&action=criar"><span class="quick-icon">↗</span><span><strong>Repor estoque</strong><small>Registre uma nova entrada</small></span><b>→</b></a>
                <?php else: ?>
                    <a href="index.php?controller=venda&action=index"><span class="quick-icon">▤</span><span><strong>Ver pedidos</strong><small>Acompanhe os pedidos recebidos</small></span><b>→</b></a>
                    <a href="index.php?controller=relatorio&action=index"><span class="quick-icon">▥</span><span><strong>Abrir relatórios</strong><small>Consulte o resumo da loja</small></span><b>→</b></a>
                <?php endif; ?>
            </article>
        </section>
        <footer class="dashboard-footer"><span>FIGHTZONE <i>•</i> PAINEL DE OPERAÇÃO</span><a href="index.php">Voltar para a loja ↗</a></footer>
    </main>
</body>
</html>

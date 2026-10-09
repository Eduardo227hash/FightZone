<?php
$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
$mensagem = $_SESSION['mensagem_conta'] ?? '';
unset($_SESSION['mensagem_conta']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#151515">
    <title><?= $perfilCliente ? 'Minha conta' : ($modo === 'cadastro' ? 'Criar conta' : 'Entrar') ?> | FightZone</title>
    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg">
    <link rel="stylesheet" href="public/assets/css/fightzone.css">
</head>
<body>
<header class="shop-header">
    <a class="wordmark" href="index.php"><span class="brand-mark">FZ</span><span>FIGHT<span class="accent">ZONE</span><small>BOXE • TREINO • PERFORMANCE</small></span></a>
    <nav><a href="index.php">Loja</a><a href="index.php?controller=loja&action=carrinho">Carrinho</a></nav>
</header>
<main class="account-shell container">
    <?php if ($mensagem !== ''): ?><div class="notice"><?= $h($mensagem) ?></div><?php endif; ?>
    <?php if ($perfilCliente): ?>
        <section class="account-card">
            <p class="eyebrow">SUA CONTA FIGHTZONE</p>
            <h1>Olá, <?= $h($perfilCliente['nome']) ?>.</h1>
            <p class="account-description">Você está conectado como <?= $h($perfilCliente['email']) ?>.</p>
            <a class="button button-primary" href="index.php?controller=cliente&action=meusPedidos">Meus pedidos</a>
            <a class="button button-primary" href="index.php">Continuar comprando</a>
            <a class="account-secondary-link" href="index.php?controller=cliente&action=sair">Sair da conta</a>
        </section>
    <?php elseif ($modo === 'cadastro'): ?>
        <section class="account-card">
            <p class="eyebrow">JUNTE-SE À FIGHTZONE</p>
            <h1>Crie sua conta</h1>
            <p class="account-description">Cadastre-se para montar seu carrinho e acompanhar suas compras.</p>
            <form method="post" action="index.php?controller=cliente&action=criar" class="account-form">
                <input type="hidden" name="csrf" value="<?= $h(ClienteController::token()) ?>">
                <label>Nome completo<input name="nome" autocomplete="name" maxlength="100" required></label>
                <label>E-mail<input name="email" type="email" autocomplete="email" maxlength="150" required></label>
                <label>Senha<input name="senha" type="password" autocomplete="new-password" minlength="8" required><small>Use pelo menos 8 caracteres.</small></label>
                <label>Confirme sua senha<input name="confirmacao_senha" type="password" autocomplete="new-password" minlength="8" required></label>
                <button class="button button-primary button-full" type="submit">Criar conta</button>
            </form>
            <p class="account-switch">Já tem cadastro? <a href="index.php?controller=cliente&action=index">Entrar</a></p>
        </section>
    <?php else: ?>
        <section class="account-card">
            <p class="eyebrow">ACESSO FIGHTZONE</p>
            <h1>Entrar</h1>
            <p class="account-description">Entre como cliente para comprar e acompanhar pedidos, ou use a conta da equipe para acessar o painel administrativo.</p>
            <form method="post" action="index.php?controller=cliente&action=login" class="account-form">
                <input type="hidden" name="csrf" value="<?= $h(ClienteController::token()) ?>">
                <label>E-mail<input name="email" type="email" autocomplete="email" required></label>
                <label>Senha<input name="senha" type="password" autocomplete="current-password" required></label>
                <button class="button button-primary button-full" type="submit">Entrar</button>
            </form>
            <p class="account-switch">Ainda não tem conta? <a href="index.php?controller=cliente&action=cadastro">Criar conta</a></p>
        </section>
    <?php endif; ?>
</main>
<footer class="shop-footer"><a class="wordmark" href="index.php"><span class="brand-mark">FZ</span><span>FIGHT<span class="accent">ZONE</span></span></a><p>Equipamento bom não luta por você. Mas ajuda.</p><span>© FightZone • Todos os direitos reservados</span></footer>
</body>
</html>

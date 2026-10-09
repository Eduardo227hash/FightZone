<?php
require_once __DIR__ . '/config/db.php';

$requeridas = [
    'usuarios',
    'categorias',
    'produtos',
    'clientes',
    'pedidos',
    'itens_pedido',
    'movimentos_estoque',
    'notas_fiscais'
];
$conexao = Database::getConnection();
$existentes = $conexao->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$faltando = array_values(array_diff($requeridas, $existentes));
if ($faltando) {
    http_response_code(500);
}
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Verificação FightZone</title><link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg"></head>
<body>
    <h1>Verificação FightZone</h1>
    <?php if ($faltando): ?>
        <p>O schema FightZone está incompleto. Importe <code>database/fightzone.sql</code>.</p>
        <p>Tabelas pendentes: <?= htmlspecialchars(implode(', ', $faltando), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <p>Banco FightZone e tabelas principais disponíveis.</p>
    <?php endif; ?>
</body>
</html>

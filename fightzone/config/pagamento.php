<?php
$configuracaoLocal = __DIR__ . '/pagamento.local.php';
$pagamento = ['pix_key' => ''];

if (is_file($configuracaoLocal)) {
    $configuracao = require $configuracaoLocal;
    if (!is_array($configuracao) || !isset($configuracao['pix_key']) || !is_string($configuracao['pix_key'])) {
        throw new RuntimeException('A configuração local do PIX é inválida.');
    }
    $pagamento['pix_key'] = trim($configuracao['pix_key']);
}

return $pagamento;

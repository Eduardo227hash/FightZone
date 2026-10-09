<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

fwrite(STDERR, "Informe a nova senha (mínimo de 8 caracteres): ");
$senha = trim((string)fgets(STDIN));
if (strlen($senha) < 8) {
    fwrite(STDERR, "A senha deve conter pelo menos 8 caracteres.\n");
    exit(1);
}

echo password_hash($senha, PASSWORD_DEFAULT) . PHP_EOL;

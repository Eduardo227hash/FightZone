<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg">
    <link rel="stylesheet" href="public/assets/css/login.css">
    <title>Equipe | FightZone</title>

</head>

<body>

    <div class="container">

        <div class="login-brand"><span class="brand-mark">FZ</span><strong>FIGHT<span>ZONE</span></strong><small>ÁREA DA EQUIPE</small></div>
        <h1>Acessar painel</h1>
        <form method="post" action="index.php?controller=auth&action=login">

            <div class="input-box">
                <label>E-mail</label>

                <input type="email" name="email" required>
            </div>

            <div class="input-box">
                <label>Senha</label>

                <input type="password" name="senha" required>
            </div>

            <button type="submit">
                Entrar
            </button>

        </form>

    </div>

</body>

</html>
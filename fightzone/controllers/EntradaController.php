<?php
require_once __DIR__ . '/../models/entrada.php';

class EntradaController
{
    public function index(): void
    {
        $this->check();
        $entradas = (new Entrada())->listarTodas();
        require __DIR__ . '/../views/entradas/listar.php';
    }

    public function criar(): void
    {
        $this->check();
        $this->onlyAdmin();
        $produtos = (new Entrada())->listarProdutos();
        $csrfEstoque = $this->token();
        require __DIR__ . '/../views/entradas/form.php';
    }

    public function store(): void
    {
        $this->check();
        $this->onlyAdmin();
        $this->validarPost();
        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $observacao = trim($_POST['observacao'] ?? '');
        if ($produtoId <= 0 || $quantidade <= 0) {
            http_response_code(422);
            die("Selecione um produto e informe uma quantidade válida.");
        }
        (new Entrada())->registrar($produtoId, $quantidade, $observacao, (int)$_SESSION['usuario_id']);
        header("Location: index.php?controller=entrada&action=index&sucesso=1");
        exit;
    }

    private function token(): string
    {
        if (empty($_SESSION['csrf_estoque'])) {
            $_SESSION['csrf_estoque'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_estoque'];
    }

    private function validarPost(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !hash_equals($this->token(), $_POST['csrf'] ?? '')
        ) {
            http_response_code(403);
            die("Solicitação inválida. Atualize a página e tente novamente.");
        }
    }

    private function check(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: index.php?controller=auth&action=form");
            exit;
        }
    }

    private function onlyAdmin(): void
    {
        if (($_SESSION['perfil'] ?? '') !== 'admin') {
            http_response_code(403);
            die("Acesso negado.");
        }
    }
}

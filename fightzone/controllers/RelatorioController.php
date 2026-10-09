<?php
require_once __DIR__ . '/../models/venda.php';
require_once __DIR__ . '/../models/produto.php';

class RelatorioController
{
    public function index(): void
    {
        $this->check();
        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fim = $_GET['fim'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim) || $inicio > $fim) {
            http_response_code(422);
            die("Período inválido.");
        }
        $vendas = (new Venda())->buscarVendasPorPeriodo($inicio, $fim);
        $produtos = (new Produto())->listarTodos();
        require __DIR__ . '/../views/relatorios.php';
    }

    private function check(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: index.php?controller=auth&action=form");
            exit;
        }
    }
}

<?php
require_once __DIR__ . '/../models/venda.php';
require_once __DIR__ . '/../models/produto.php';
require_once __DIR__ . '/../models/cliente.php';

class VendaController
{
    public function index(): void
    {
        $this->check();
        $model = new Venda();
        $vendas = $model->listarTodas();
        require __DIR__ . '/../views/vendas/listar.php';
    }

    private function check(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: index.php?controller=auth&action=form");
            exit;
        }
    }
}

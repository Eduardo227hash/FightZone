<?php
require_once 'models/pagamento.php';

class PagamentoController
{
    public function store()
    {

        $id_venda = $_POST['id_venda'] ?? 0;
        $forma    = $_POST['forma_pagamento'] ?? '';
        $banco    = $_POST['banco'] ?? '';
        $parcelas = $_POST['parcelas'] ?? 1;
        $juros    = $_POST['juros'] ?? 0.00;
        $status   = $_POST['status_pagamento'] ?? 'pendente';

        $model = new Pagamento();

        if ($model->registrar($id_venda, $forma, $banco, $parcelas, $juros, $status)) {
            header("Location: index.php?controller=pagamento&action=listar&sucesso=1");
            exit;
        } else {
            die("Erro ao registrar o pagamento no banco de dados.");
        }
    }
}

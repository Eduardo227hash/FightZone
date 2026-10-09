<?php
require_once 'models/funcionario.php';

class FuncionarioController
{
    public function store()
    {

        $nome     = $_POST['nome'] ?? '';
        $cargo    = $_POST['cargo'] ?? '';
        $comissao = $_POST['comissao'] ?? 0.00;

        $model = new Funcionario();

        if ($model->cadastrar($nome, $cargo, (float)$comissao)) {
            // Redireciona em caso de sucesso
            header("Location: index.php?controller=funcionario&action=listar&sucesso=1");
            exit;
        } else {
            die("Erro ao cadastrar o funcionário no banco de dados.");
        }
    }
}

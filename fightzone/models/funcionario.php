<?php
class Funcionario
{
    private PDO $conn;

    public function __construct()
    {

        $this->conn = Database::getConnection();
    }

    public function cadastrar(string $nome, string $cargo, float $comissao)
    {
        $sql = "INSERT INTO funcionarios (nome, cargo, comissao) 
                VALUES (:nome, :cargo, :comissao)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            'nome'     => $nome,
            'cargo'    => $cargo,
            'comissao' => $comissao
        ]);
    }
}

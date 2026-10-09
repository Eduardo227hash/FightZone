<?php
class Pagamento
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function registrar(int $id_venda, string $forma, string $banco, int $parcelas, float $juros, string $status)
    {
        $sql = "INSERT INTO pagamentos (id_venda, forma_pagamento, banco, parcelas, juros, status_pagamento) 
                VALUES (:venda, :forma, :banco, :parcelas, :juros, :status)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            'venda'    => $id_venda,
            'forma'    => $forma,
            'banco'    => $banco,
            'parcelas' => $parcelas,
            'juros'    => $juros,
            'status'   => $status
        ]);
    }
}

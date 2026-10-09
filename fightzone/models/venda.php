<?php
class Venda
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function listarTodas(): array
    {
        return $this->buscarVendasPorPeriodo('2000-01-01', date('Y-m-d'));
    }

    public function buscarVendasPorPeriodo(string $inicio, string $fim): array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.id AS id_pedido, p.created_at AS criado_em,
                    COALESCE(p.cliente_nome, u.nome) AS cliente_nome,
                    COALESCE(p.cliente_email, u.email) AS cliente_email,
                    p.total, p.status AS status_pedido, p.pagamento AS forma_pagamento
             FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
             WHERE DATE(p.created_at) BETWEEN :inicio AND :fim ORDER BY p.created_at DESC"
        );
        $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
        return $stmt->fetchAll();
    }

    public function calcularTotalMensal(int $mes, int $ano): float
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM pedidos
             WHERE MONTH(created_at) = :mes AND YEAR(created_at) = :ano"
        );
        $stmt->execute([':mes' => $mes, ':ano' => $ano]);
        return (float)$stmt->fetchColumn();
    }
}

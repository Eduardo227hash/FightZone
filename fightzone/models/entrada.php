<?php
require_once __DIR__ . '/../config/db.php';

class Entrada
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function listarTodas(): array
    {
        return $this->conn->query(
            "SELECT m.*, p.nome AS nome_produto, u.nome AS nome_usuario
             FROM movimentos_estoque m
             JOIN produtos p ON p.id = m.produto_id
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             ORDER BY m.created_at DESC, m.id DESC"
        )->fetchAll();
    }

    public function listarProdutos(): array
    {
        return $this->conn->query(
            "SELECT id, nome FROM produtos WHERE ativo = 1 ORDER BY nome"
        )->fetchAll();
    }

    public function registrar(int $produtoId, int $quantidade, string $observacao, int $usuarioId): void
    {
        if ($quantidade < 1) {
            throw new InvalidArgumentException("A quantidade deve ser maior que zero.");
        }
        $this->conn->beginTransaction();
        try {
            $produto = $this->conn->prepare(
                "SELECT id FROM produtos WHERE id = :id AND ativo = 1 FOR UPDATE"
            );
            $produto->execute([':id' => $produtoId]);
            if (!$produto->fetch()) {
                throw new RuntimeException("Produto não encontrado ou inativo.");
            }
            $movimento = $this->conn->prepare(
                "INSERT INTO movimentos_estoque
                 (produto_id, tipo, quantidade, observacao, usuario_id, created_at)
                 VALUES (:produto, 'entrada', :quantidade, :observacao, :usuario, NOW())"
            );
            $movimento->execute([
                ':produto' => $produtoId,
                ':quantidade' => $quantidade,
                ':observacao' => $observacao !== '' ? $observacao : 'Reposição de estoque',
                ':usuario' => $usuarioId
            ]);
            $estoque = $this->conn->prepare(
                "UPDATE produtos SET estoque = estoque + :quantidade WHERE id = :produto"
            );
            $estoque->execute([':quantidade' => $quantidade, ':produto' => $produtoId]);
            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}

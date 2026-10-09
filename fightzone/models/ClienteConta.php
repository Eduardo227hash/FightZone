<?php
require_once __DIR__ . '/../config/db.php';

class ClienteConta
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nome, email, senha, perfil, ativo
             FROM usuarios WHERE email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPerfil(int $usuarioId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.nome, u.email, c.cpf, c.telefone, c.cep, c.logradouro,
                    c.numero, c.complemento, c.bairro, c.cidade, c.uf
             FROM usuarios u
             LEFT JOIN clientes c ON c.usuario_id = u.id
             WHERE u.id = :id AND u.perfil = 'cliente' AND u.ativo = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public function cadastrar(string $nome, string $email, string $senha): int
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO usuarios (nome, email, senha, perfil, ativo, created_at)
                 VALUES (:nome, :email, :senha, 'cliente', 1, NOW())"
            );
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => password_hash($senha, PASSWORD_DEFAULT)
            ]);
            $usuarioId = (int)$this->conn->lastInsertId();
            $cliente = $this->conn->prepare(
                "INSERT INTO clientes (usuario_id, nome) VALUES (:usuario, :nome)"
            );
            $cliente->execute([':usuario' => $usuarioId, ':nome' => $nome]);
            $this->conn->commit();
            return $usuarioId;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}

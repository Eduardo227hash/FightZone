<?php
require_once __DIR__ . '/../config/db.php';

class Cliente
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function listarTodos(): array
    {
        $sql = "SELECT * FROM clientes ORDER BY nome ASC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function registrar(string $nome, string $email, string $cpf_cnpj, string $telefone): bool
    {
        $sql = "INSERT INTO clientes (nome, email, cpf_cnpj, telefone) 
                VALUES (:nome, :email, :cpf, :tel)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'nome'  => $nome,
            'email' => $email,
            'cpf'   => $cpf_cnpj,
            'tel'   => $telefone
        ]);
    }

    public function atualizar(int $id, string $nome, string $email, string $telefone): bool
    {
        $sql = "UPDATE clientes SET nome = :nome, email = :email, telefone = :tel WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id'    => $id,
            'nome'  => $nome,
            'email' => $email,
            'tel'   => $telefone
        ]);
    }

    public function deletar(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM clientes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

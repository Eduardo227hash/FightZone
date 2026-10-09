<?php
require_once __DIR__ . '/../config/db.php';

class Categoria
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function listarTodas(): array
    {
        return $this->conn->query("SELECT id, nome, ativo FROM categorias ORDER BY nome")->fetchAll();
    }

    public function listarAtivas(): array
    {
        return $this->conn->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, nome, ativo FROM categorias WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function inserir(string $nome): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO categorias (nome, slug, ativo) VALUES (:nome, :slug, 1)"
        );
        $stmt->execute([':nome' => $nome, ':slug' => $this->slug($nome)]);
        return (int)$this->conn->lastInsertId();
    }

    public function atualizar(int $id, string $nome): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE categorias SET nome = :nome, slug = :slug WHERE id = :id"
        );
        $stmt->execute([':id' => $id, ':nome' => $nome, ':slug' => $this->slug($nome)]);
    }

    public function setAtivo(int $id, bool $ativo): void
    {
        $stmt = $this->conn->prepare("UPDATE categorias SET ativo = :ativo WHERE id = :id");
        $stmt->execute([':id' => $id, ':ativo' => $ativo ? 1 : 0]);
    }

    private function slug(string $texto): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii), '-'));
        return $slug !== '' ? $slug : bin2hex(random_bytes(6));
    }
}

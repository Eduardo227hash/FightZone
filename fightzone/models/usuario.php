<?php
require_once __DIR__ . '/../config/db.php';

class Usuario
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

}

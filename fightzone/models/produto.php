<?php
require_once __DIR__ . '/../config/db.php';

class Produto
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function listarTodos(): array
    {
        return $this->conn->query(
            "SELECT p.*, p.id AS id_produto, p.slug AS sku, c.nome AS categoria_nome
             FROM produtos p LEFT JOIN categorias c ON c.id = p.categoria_id
             ORDER BY p.id DESC"
        )->fetchAll();
    }

    public function listarDisponiveis(): array
    {
        return $this->conn->query(
            "SELECT p.*, p.id AS id_produto, p.slug AS sku, c.nome AS categoria_nome
             FROM produtos p LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE p.ativo = 1 AND p.estoque > 0 AND c.ativo = 1 ORDER BY p.nome"
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT p.*, p.id AS id_produto, p.slug AS sku FROM produtos p WHERE p.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function inserir(
        string $nome,
        string $descricao,
        int $categoriaId,
        string $marca,
        float $preco,
        int $estoque,
        float $peso,
        float $altura,
        float $largura,
        float $comprimento,
        ?string $imagem
    ): int {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO produtos
                 (categoria_id, nome, slug, marca, descricao, preco, peso, altura, largura, comprimento,
                  estoque, imagem, ativo, created_at)
                 VALUES (:categoria, :nome, :sku, :marca, :descricao, :preco, :peso, :altura, :largura,
                  :comprimento, :estoque, :imagem, 1, NOW())"
            );
            $stmt->execute([
                ':categoria' => $categoriaId,
                ':nome' => $nome,
                ':sku' => 'TEMP-' . bin2hex(random_bytes(16)),
                ':marca' => $marca,
                ':descricao' => $descricao,
                ':preco' => $preco,
                ':peso' => $peso,
                ':altura' => $altura,
                ':largura' => $largura,
                ':comprimento' => $comprimento,
                ':estoque' => $estoque,
                ':imagem' => $imagem
            ]);

            $id = (int)$this->conn->lastInsertId();
            $numeroSku = $id;
            do {
                $sku = 'FZ-' . str_pad((string)$numeroSku, 6, '0', STR_PAD_LEFT);
                $verificarSku = $this->conn->prepare("SELECT 1 FROM produtos WHERE slug = :sku");
                $verificarSku->execute([':sku' => $sku]);
                $numeroSku++;
            } while ($verificarSku->fetchColumn());

            $atualizarSku = $this->conn->prepare("UPDATE produtos SET slug = :sku WHERE id = :id");
            $atualizarSku->execute([':sku' => $sku, ':id' => $id]);
            $this->conn->commit();

            return $id;
        } catch (Throwable $erro) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $erro;
        }
    }

    public function atualizar(
        int $id,
        string $nome,
        string $descricao,
        int $categoriaId,
        string $marca,
        float $preco,
        int $estoque,
        float $peso,
        float $altura,
        float $largura,
        float $comprimento,
        ?string $imagem
    ): void {
        $sql = "UPDATE produtos SET categoria_id = :categoria, nome = :nome,
                marca = :marca, descricao = :descricao, preco = :preco, estoque = :estoque,
                peso = :peso, altura = :altura, largura = :largura, comprimento = :comprimento,
                ativo = 1";
        if ($imagem !== null) {
            $sql .= ", imagem = :imagem";
        }
        $sql .= " WHERE id = :id";
        $params = [
            ':id' => $id,
            ':categoria' => $categoriaId,
            ':nome' => $nome,
            ':marca' => $marca,
            ':descricao' => $descricao,
            ':preco' => $preco,
            ':estoque' => $estoque,
            ':peso' => $peso,
            ':altura' => $altura,
            ':largura' => $largura,
            ':comprimento' => $comprimento
        ];
        if ($imagem !== null) {
            $params[':imagem'] = $imagem;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
    }

    public function deletar(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE produtos SET ativo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

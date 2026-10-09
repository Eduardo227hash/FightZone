<?php
require_once __DIR__ . '/../config/db.php';

class Compra
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function finalizar(array $cliente, array $itens, float $frete, string $formaPagamento): array
    {
        $this->conn->beginTransaction();
        try {
            $subtotal = 0.0;
            $itensConfirmados = [];
            $buscar = $this->conn->prepare(
                "SELECT id, nome, preco, estoque FROM produtos
                 WHERE id = :id AND ativo = 1 FOR UPDATE"
            );
            foreach ($itens as $id => $quantidade) {
                $buscar->execute([':id' => (int)$id]);
                $produto = $buscar->fetch();
                if (!$produto || $quantidade < 1 || (int)$produto['estoque'] < $quantidade) {
                    throw new RuntimeException("Estoque insuficiente para um ou mais itens do carrinho.");
                }
                $produto['quantidade'] = $quantidade;
                $produto['total'] = (float)$produto['preco'] * $quantidade;
                $subtotal += $produto['total'];
                $itensConfirmados[] = $produto;
            }
            if (!$itensConfirmados) {
                throw new RuntimeException("O carrinho está vazio.");
            }

            $usuarioId = (int)($cliente['usuario_id'] ?? 0);
            if ($usuarioId <= 0) {
                throw new RuntimeException("Entre na sua conta antes de finalizar a compra.");
            }

            $clienteStmt = $this->conn->prepare(
                "INSERT INTO clientes
                 (usuario_id, nome, cpf, telefone, cep, logradouro, numero, complemento, bairro, cidade, uf)
                 VALUES (:usuario, :nome, :cpf, :telefone, :cep, :logradouro, :numero,
                  :complemento, NULL, :cidade, :uf)
                 ON DUPLICATE KEY UPDATE nome = VALUES(nome), cpf = VALUES(cpf),
                  telefone = VALUES(telefone), cep = VALUES(cep), logradouro = VALUES(logradouro),
                  numero = VALUES(numero), complemento = VALUES(complemento),
                  cidade = VALUES(cidade), uf = VALUES(uf)"
            );
            $clienteStmt->execute([
                ':usuario' => $usuarioId,
                ':nome' => $cliente['nome'],
                ':cpf' => $cliente['documento'] ?: null,
                ':telefone' => $cliente['telefone'] ?: null,
                ':cep' => $cliente['cep'],
                ':logradouro' => $cliente['endereco'],
                ':numero' => $cliente['numero'],
                ':complemento' => $cliente['complemento'] ?: null,
                ':cidade' => $cliente['cidade'],
                ':uf' => $cliente['uf']
            ]);

            $codigo = bin2hex(random_bytes(24));
            $endereco = $cliente['endereco'] . ', ' . $cliente['numero'];
            if ($cliente['complemento'] !== '') {
                $endereco .= ' - ' . $cliente['complemento'];
            }
            $endereco .= ' - ' . $cliente['cidade'] . '/' . $cliente['uf'];
            $pagamento = $formaPagamento;
            $pedidoStmt = $this->conn->prepare(
                "INSERT INTO pedidos
                 (usuario_id, subtotal, frete, total, status, pagamento, cep_entrega, endereco_entrega,
                  created_at, codigo_acesso, cliente_nome, cliente_email, cliente_documento, cliente_telefone,
                  status_pagamento)
                 VALUES (:usuario, :subtotal, :frete, :total, 'aguardando', :pagamento, :cep, :endereco,
                  NOW(), :codigo, :nome, :email, :documento, :telefone, 'pendente')"
            );
            $pedidoStmt->execute([
                ':usuario' => $usuarioId,
                ':subtotal' => $subtotal,
                ':frete' => $frete,
                ':total' => $subtotal + $frete,
                ':pagamento' => $pagamento,
                ':cep' => $cliente['cep'],
                ':endereco' => $endereco,
                ':codigo' => $codigo,
                ':nome' => $cliente['nome'],
                ':email' => $cliente['email'],
                ':documento' => $cliente['documento'] ?: null,
                ':telefone' => $cliente['telefone'] ?: null
            ]);
            $pedidoId = (int)$this->conn->lastInsertId();

            $itemStmt = $this->conn->prepare(
                "INSERT INTO itens_pedido (pedido_id, produto_id, produto_nome, quantidade, preco_unitario)
                 VALUES (:pedido, :produto, :nome, :quantidade, :preco)"
            );
            $baixarEstoque = $this->conn->prepare("UPDATE produtos SET estoque = estoque - :quantidade WHERE id = :id");
            $movimento = $this->conn->prepare(
                "INSERT INTO movimentos_estoque (produto_id, tipo, quantidade, observacao, usuario_id, created_at)
                 VALUES (:produto, 'saida', :quantidade, :observacao, NULL, NOW())"
            );
            foreach ($itensConfirmados as $item) {
                $itemStmt->execute([
                    ':pedido' => $pedidoId,
                    ':produto' => $item['id'],
                    ':nome' => $item['nome'],
                    ':quantidade' => $item['quantidade'],
                    ':preco' => $item['preco']
                ]);
                $baixarEstoque->execute([
                    ':quantidade' => $item['quantidade'],
                    ':id' => $item['id']
                ]);
                $movimento->execute([
                    ':produto' => $item['id'],
                    ':quantidade' => $item['quantidade'],
                    ':observacao' => 'Pedido #' . $pedidoId
                ]);
            }

            $nota = $this->conn->prepare(
                "INSERT INTO notas_fiscais (pedido_id, numero, valor_total, created_at)
                 VALUES (:pedido, :numero, :total, NOW())"
            );
            $nota->execute([
                ':pedido' => $pedidoId,
                ':numero' => 'PED-' . $pedidoId,
                ':total' => $subtotal + $frete
            ]);
            $this->conn->commit();
            return ['id' => $pedidoId, 'codigo' => $codigo];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function buscarComItens(int $id, string $codigo): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.id AS id_pedido, p.codigo_acesso,
                    COALESCE(p.cliente_nome, u.nome) AS cliente_nome,
                    COALESCE(p.cliente_email, u.email) AS cliente_email,
                    p.cliente_documento, p.cliente_telefone, p.cep_entrega AS cep,
                    p.endereco_entrega AS endereco, p.pagamento AS forma_pagamento,
                    p.subtotal, p.frete, p.total, p.status, p.status_pagamento,
                    p.created_at AS criado_em
             FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.id = :id AND p.codigo_acesso = :codigo"
        );
        $stmt->execute([':id' => $id, ':codigo' => $codigo]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            return null;
        }
        $itens = $this->conn->prepare(
            "SELECT COALESCE(i.produto_nome, p.nome) AS produto_nome, i.quantidade, i.preco_unitario
             FROM itens_pedido i LEFT JOIN produtos p ON p.id = i.produto_id
             WHERE i.pedido_id = :id ORDER BY i.id"
        );
        $itens->execute([':id' => $id]);
        $pedido['itens'] = $itens->fetchAll();
        return $pedido;
    }

    public function listarTodos(): array
    {
        return $this->conn->query(
            "SELECT p.id AS id_pedido, p.created_at AS criado_em,
                    COALESCE(p.cliente_nome, u.nome) AS cliente_nome,
                    COALESCE(p.cliente_email, u.email) AS cliente_email,
                    p.pagamento AS forma_pagamento, p.status AS status_pedido, p.total
             FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.created_at DESC"
        )->fetchAll();
    }

    public function listarDoCliente(int $usuarioId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT p.id AS id_pedido, p.codigo_acesso, p.created_at AS criado_em,
                    p.status, p.status_pagamento, p.pagamento AS forma_pagamento,
                    p.subtotal, p.frete, p.total, p.cep_entrega AS cep,
                    p.endereco_entrega AS endereco,
                    COALESCE(i.produto_nome, pr.nome) AS produto_nome,
                    i.quantidade, i.preco_unitario
             FROM pedidos p
             LEFT JOIN itens_pedido i ON i.pedido_id = p.id
             LEFT JOIN produtos pr ON pr.id = i.produto_id
             WHERE p.usuario_id = :usuario
             ORDER BY p.created_at DESC, i.id ASC"
        );
        $stmt->execute([':usuario' => $usuarioId]);

        $pedidos = [];
        foreach ($stmt->fetchAll() as $linha) {
            $id = (int)$linha['id_pedido'];
            if (!isset($pedidos[$id])) {
                $pedidos[$id] = [
                    'id_pedido' => $id,
                    'codigo_acesso' => $linha['codigo_acesso'],
                    'criado_em' => $linha['criado_em'],
                    'status' => $linha['status'],
                    'status_pagamento' => $linha['status_pagamento'],
                    'forma_pagamento' => $linha['forma_pagamento'],
                    'subtotal' => $linha['subtotal'],
                    'frete' => $linha['frete'],
                    'total' => $linha['total'],
                    'cep' => $linha['cep'],
                    'endereco' => $linha['endereco'],
                    'itens' => []
                ];
            }
            if ($linha['produto_nome'] !== null) {
                $pedidos[$id]['itens'][] = [
                    'produto_nome' => $linha['produto_nome'],
                    'quantidade' => (int)$linha['quantidade'],
                    'preco_unitario' => (float)$linha['preco_unitario']
                ];
            }
        }

        return array_values($pedidos);
    }

    public function cancelarPedidoCliente(int $pedidoId, int $usuarioId): bool
    {
        $this->conn->beginTransaction();
        try {
            $pedidoStmt = $this->conn->prepare(
                "SELECT status, status_pagamento
                 FROM pedidos
                 WHERE id = :pedido AND usuario_id = :usuario
                 FOR UPDATE"
            );
            $pedidoStmt->execute([
                ':pedido' => $pedidoId,
                ':usuario' => $usuarioId
            ]);
            $pedido = $pedidoStmt->fetch();

            if (
                !$pedido ||
                $pedido['status'] !== 'aguardando' ||
                $pedido['status_pagamento'] !== 'pendente'
            ) {
                $this->conn->rollBack();
                return false;
            }

            $itensStmt = $this->conn->prepare(
                "SELECT produto_id, quantidade
                 FROM itens_pedido
                 WHERE pedido_id = :pedido
                 ORDER BY produto_id
                 FOR UPDATE"
            );
            $itensStmt->execute([':pedido' => $pedidoId]);
            $itens = $itensStmt->fetchAll();
            if (!$itens) {
                throw new RuntimeException('Não foi possível cancelar um pedido sem itens.');
            }

            $estoqueStmt = $this->conn->prepare(
                "UPDATE produtos SET estoque = estoque + :quantidade WHERE id = :produto"
            );
            $movimentoStmt = $this->conn->prepare(
                "INSERT INTO movimentos_estoque
                 (produto_id, tipo, quantidade, observacao, usuario_id, created_at)
                 VALUES (:produto, 'entrada', :quantidade, :observacao, :usuario, NOW())"
            );
            foreach ($itens as $item) {
                $estoqueStmt->execute([
                    ':quantidade' => (int)$item['quantidade'],
                    ':produto' => (int)$item['produto_id']
                ]);
                if ($estoqueStmt->rowCount() !== 1) {
                    throw new RuntimeException('Não foi possível devolver um produto ao estoque.');
                }
                $movimentoStmt->execute([
                    ':produto' => (int)$item['produto_id'],
                    ':quantidade' => (int)$item['quantidade'],
                    ':observacao' => 'Cancelamento pedido #' . $pedidoId,
                    ':usuario' => $usuarioId
                ]);
            }

            $cancelarStmt = $this->conn->prepare(
                "UPDATE pedidos
                 SET status = 'cancelado', status_pagamento = 'cancelado'
                 WHERE id = :pedido AND usuario_id = :usuario
                   AND status = 'aguardando' AND status_pagamento = 'pendente'"
            );
            $cancelarStmt->execute([
                ':pedido' => $pedidoId,
                ':usuario' => $usuarioId
            ]);
            if ($cancelarStmt->rowCount() !== 1) {
                throw new RuntimeException('O pedido mudou de status e não pode ser cancelado.');
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function resumoDashboard(): array
    {
        return [
            'vendas' => (float)$this->conn->query(
                "SELECT COALESCE(SUM(total), 0) FROM pedidos
                 WHERE status = 'pago'
                 AND MONTH(created_at) = MONTH(CURRENT_DATE())
                 AND YEAR(created_at) = YEAR(CURRENT_DATE())"
            )->fetchColumn(),
            'pedidos' => (int)$this->conn->query("SELECT COUNT(*) FROM pedidos WHERE status <> 'cancelado'")->fetchColumn(),
            'pedidos_mes' => (int)$this->conn->query(
                "SELECT COUNT(*) FROM pedidos
                 WHERE status <> 'cancelado'
                 AND MONTH(created_at) = MONTH(CURRENT_DATE())
                 AND YEAR(created_at) = YEAR(CURRENT_DATE())"
            )->fetchColumn(),
            'aguardando' => (int)$this->conn->query(
                "SELECT COUNT(*) FROM pedidos WHERE status = 'aguardando'"
            )->fetchColumn(),
            'estoque_baixo' => (int)$this->conn->query(
                "SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND estoque <= 5"
            )->fetchColumn(),
            'produtos' => (int)$this->conn->query(
                "SELECT COUNT(*) FROM produtos WHERE ativo = 1"
            )->fetchColumn()
        ];
    }
}

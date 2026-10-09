<?php
require_once __DIR__ . '/../models/produto.php';
require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/../models/ClienteConta.php';
require_once __DIR__ . '/../services/InvoicePdf.php';

class LojaController
{
    private array $pagamentoConfig;

    public function __construct()
    {
        $this->pagamentoConfig = require __DIR__ . '/../config/pagamento.php';
    }

    public function catalogo(): void
    {
        $produtos = (new Produto())->listarDisponiveis();
        $carrinhoQuantidade = !empty($_SESSION['cliente_usuario_id'])
            ? array_sum($_SESSION['carrinho'] ?? [])
            : 0;
        $busca = trim($_GET['q'] ?? '');
        if ($busca !== '') {
            $termo = function_exists('mb_strtolower') ? mb_strtolower($busca) : strtolower($busca);
            $produtos = array_values(array_filter($produtos, function ($produto) use ($termo) {
                $texto = $produto['nome'] . ' ' . ($produto['descricao'] ?? '') . ' ' . ($produto['categoria_nome'] ?? '');
                $texto = function_exists('mb_strtolower') ? mb_strtolower($texto) : strtolower($texto);
                return strpos($texto, $termo) !== false;
            }));
        }
        $pagina = 'catalogo';
        require __DIR__ . '/../views/loja.php';
    }

    public function adicionar(): void
    {
        $this->validarPost();
        $this->exigirCliente();
        $id = (int)($_POST['produto_id'] ?? 0);
        $produto = (new Produto())->buscarPorId($id);
        if (!$produto || !(int)$produto['ativo'] || (int)$produto['estoque'] < 1) {
            $_SESSION['mensagem_loja'] = 'Este produto não está disponível no momento.';
            $this->ir('catalogo');
        }
        $atual = (int)($_SESSION['carrinho'][$id] ?? 0);
        $_SESSION['carrinho'][$id] = min((int)$produto['estoque'], $atual + 1);
        $this->ir('carrinho');
    }

    public function carrinho(): void
    {
        $this->exigirCliente();
        $itens = $this->carregarCarrinho();
        $subtotal = $this->subtotal($itens);
        $frete = $_SESSION['frete_estimado'] ?? null;
        $cep = $_SESSION['cep_frete'] ?? '';
        $carrinhoQuantidade = array_sum($_SESSION['carrinho'] ?? []);
        $pagina = 'carrinho';
        require __DIR__ . '/../views/loja.php';
    }

    public function atualizarCarrinho(): void
    {
        $this->validarPost();
        $this->exigirCliente();
        foreach (($_POST['quantidade'] ?? []) as $id => $quantidade) {
            $id = (int)$id;
            $quantidade = (int)$quantidade;
            if ($id <= 0) {
                continue;
            }
            if ($quantidade <= 0) {
                unset($_SESSION['carrinho'][$id]);
                continue;
            }
            $produto = (new Produto())->buscarPorId($id);
            if (!$produto || !(int)$produto['ativo']) {
                unset($_SESSION['carrinho'][$id]);
                continue;
            }
            $_SESSION['carrinho'][$id] = min($quantidade, (int)$produto['estoque']);
        }
        $this->ir('carrinho');
    }

    public function frete(): void
    {
        $this->validarPost();
        $this->exigirCliente();
        $cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
        if (!preg_match('/^\d{8}$/', $cep)) {
            $_SESSION['mensagem_loja'] = 'Informe um CEP válido com 8 números.';
            $this->ir('carrinho');
        }
        $itens = $this->carregarCarrinho();
        if (!$itens) {
            $this->ir('catalogo');
        }
        $_SESSION['cep_frete'] = $cep;
        $_SESSION['frete_estimado'] = $this->calcularFrete($cep, $itens);
        $this->ir('carrinho');
    }

    public function checkout(): void
    {
        $perfilCliente = $this->exigirCliente();
        $itens = $this->carregarCarrinho();
        if (!$itens) {
            $this->ir('carrinho');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarPost();
            $cliente = [
                'usuario_id' => (int)$perfilCliente['id'],
                'nome' => $perfilCliente['nome'],
                'email' => $perfilCliente['email'],
                'documento' => preg_replace('/\D/', '', $_POST['documento'] ?? ''),
                'telefone' => trim($_POST['telefone'] ?? ($perfilCliente['telefone'] ?? '')),
                'cep' => preg_replace('/\D/', '', $_POST['cep'] ?? ''),
                'endereco' => trim($_POST['endereco'] ?? ''),
                'numero' => trim($_POST['numero'] ?? ''),
                'complemento' => trim($_POST['complemento'] ?? ''),
                'cidade' => trim($_POST['cidade'] ?? ''),
                'uf' => strtoupper(trim($_POST['uf'] ?? ''))
            ];
            $forma = $_POST['forma_pagamento'] ?? '';
            if (
                $cliente['nome'] === '' || !$cliente['email'] ||
                !preg_match('/^\d{8}$/', $cliente['cep']) ||
                $cliente['endereco'] === '' || $cliente['numero'] === '' ||
                $cliente['cidade'] === '' || !preg_match('/^[A-Z]{2}$/', $cliente['uf']) ||
                !in_array($forma, ['pix', 'cartao', 'boleto'], true)
            ) {
                http_response_code(422);
                die("Revise seus dados de entrega e a forma de pagamento.");
            }
            $frete = $this->calcularFrete($cliente['cep'], $itens);
            try {
                $pedido = (new Compra())->finalizar($cliente, $_SESSION['carrinho'], $frete, $forma);
                unset($_SESSION['carrinho'], $_SESSION['frete_estimado'], $_SESSION['cep_frete']);
                $_SESSION['ultimo_pedido'] = $pedido;
                $this->ir('confirmacao', ['id' => $pedido['id'], 'token' => $pedido['codigo']]);
            } catch (RuntimeException $e) {
                $_SESSION['mensagem_loja'] = $e->getMessage();
                $this->ir('carrinho');
            }
        }
        $subtotal = $this->subtotal($itens);
        $cep = $_SESSION['cep_frete'] ?? ($perfilCliente['cep'] ?? '');
        $frete = $cep !== '' ? $this->calcularFrete($cep, $itens) : null;
        $carrinhoQuantidade = array_sum($_SESSION['carrinho'] ?? []);
        $pixKey = $this->pagamentoConfig['pix_key'];
        $pagina = 'checkout';
        require __DIR__ . '/../views/loja.php';
    }

    public function confirmacao(): void
    {
        $pedido = $this->buscarPedidoAutorizado();
        $pixKey = $this->pagamentoConfig['pix_key'];
        $pagina = 'confirmacao';
        $carrinhoQuantidade = 0;
        require __DIR__ . '/../views/loja.php';
    }

    public function notaFiscal(): void
    {
        $pedido = $this->buscarPedidoAutorizado();
        (new InvoicePdf())->enviar($pedido);
    }

    public static function token(): string
    {
        if (empty($_SESSION['csrf_loja'])) {
            $_SESSION['csrf_loja'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_loja'];
    }

    private function validarPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(self::token(), $_POST['csrf'] ?? '')) {
            http_response_code(403);
            die("Solicitação inválida. Atualize a página e tente novamente.");
        }
    }

    private function exigirCliente(): array
    {
        $usuarioId = (int)($_SESSION['cliente_usuario_id'] ?? 0);
        $perfil = $usuarioId > 0 ? (new ClienteConta())->buscarPerfil($usuarioId) : null;
        if (!$perfil) {
            unset($_SESSION['cliente_usuario_id'], $_SESSION['cliente_nome'], $_SESSION['cliente_email']);
            $_SESSION['mensagem_conta'] = 'Entre ou crie uma conta para usar o carrinho e comprar.';
            header('Location: index.php?controller=cliente&action=index');
            exit;
        }
        return $perfil;
    }

    private function carregarCarrinho(): array
    {
        $carrinho = $_SESSION['carrinho'] ?? [];
        if (!$carrinho) {
            return [];
        }
        $ids = array_filter(array_map('intval', array_keys($carrinho)));
        if (!$ids) {
            return [];
        }
        $produtos = (new Produto())->listarDisponiveis();
        $porId = [];
        foreach ($produtos as $produto) {
            $porId[(int)$produto['id_produto']] = $produto;
        }
        $itens = [];
        foreach ($ids as $id) {
            if (!isset($porId[$id])) {
                unset($_SESSION['carrinho'][$id]);
                continue;
            }
            $produto = $porId[$id];
            $produto['quantidade'] = min((int)$carrinho[$id], (int)$produto['estoque']);
            $produto['total'] = $produto['quantidade'] * (float)$produto['preco'];
            $_SESSION['carrinho'][$id] = $produto['quantidade'];
            $itens[] = $produto;
        }
        return $itens;
    }

    private function subtotal(array $itens): float
    {
        return array_reduce($itens, function ($total, $item) {
            return $total + (float)$item['total'];
        }, 0.0);
    }

    private function calcularFrete(string $cep, array $itens): float
    {
        $faixas = [
            [0, 19, 16.90], [20, 39, 19.90], [40, 65, 26.90],
            [66, 69, 34.90], [70, 79, 24.90], [80, 99, 21.90]
        ];
        $prefixo = (int)substr($cep, 0, 2);
        $base = 24.90;
        foreach ($faixas as $faixa) {
            if ($prefixo >= $faixa[0] && $prefixo <= $faixa[1]) {
                $base = $faixa[2];
                break;
            }
        }
        $peso = 0.0;
        foreach ($itens as $item) {
            $pesoCubado = (float)$item['altura'] * (float)$item['largura'] * (float)$item['comprimento'] / 6000;
            $peso += max((float)$item['peso'], $pesoCubado) * (int)$item['quantidade'];
        }
        return round($base + max(0, $peso - 1) * 3.5, 2);
    }

    private function buscarPedidoAutorizado(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        $token = (string)($_GET['token'] ?? '');
        if ($id <= 0 || !preg_match('/^[a-f0-9]{48}$/', $token)) {
            http_response_code(404);
            die("Pedido não encontrado.");
        }
        $pedido = (new Compra())->buscarComItens($id, $token);
        if (!$pedido) {
            http_response_code(404);
            die("Pedido não encontrado.");
        }
        return $pedido;
    }

    private function ir(string $action, array $params = []): void
    {
        $params = array_merge(['controller' => 'loja', 'action' => $action], $params);
        header('Location: index.php?' . http_build_query($params));
        exit;
    }
}

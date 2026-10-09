<?php
require_once __DIR__ . '/../models/ClienteConta.php';
require_once __DIR__ . '/../models/Compra.php';

class ClienteController
{
    public function index(): void
    {
        if (
            isset($_SESSION['usuario_id']) &&
            in_array($_SESSION['perfil'] ?? '', ['admin', 'vendedor'], true)
        ) {
            header('Location: index.php?controller=auth&action=dashboard');
            exit;
        }
        $perfilCliente = $this->perfilAtual();
        $modo = 'login';
        require __DIR__ . '/../views/conta.php';
    }

    public function cadastro(): void
    {
        if ($this->perfilAtual()) {
            $this->irLoja();
        }
        $perfilCliente = null;
        $modo = 'cadastro';
        require __DIR__ . '/../views/conta.php';
    }

    public function meusPedidos(): void
    {
        $perfilCliente = $this->perfilAtual();
        if (!$perfilCliente) {
            $_SESSION['mensagem_conta'] = 'Entre na sua conta para acompanhar seus pedidos.';
            $this->ir('index');
        }

        $pedidos = (new Compra())->listarDoCliente((int)$perfilCliente['id']);
        foreach ($pedidos as &$pedido) {
            $previsao = new DateTimeImmutable($pedido['criado_em']);
            $diasUteis = 0;
            while ($diasUteis < 7) {
                $previsao = $previsao->modify('+1 day');
                if ((int)$previsao->format('N') < 6) {
                    $diasUteis++;
                }
            }
            $pedido['previsao_entrega'] = $previsao->format('d/m/Y');
        }
        unset($pedido);

        require __DIR__ . '/../views/pedidos_cliente.php';
    }

    public function cancelarPedido(): void
    {
        $this->validarPost();
        $perfilCliente = $this->perfilAtual();
        if (!$perfilCliente) {
            $_SESSION['mensagem_conta'] = 'Entre na sua conta para cancelar um pedido.';
            $this->ir('index');
        }

        $pedidoId = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
        if (!$pedidoId || $pedidoId < 1) {
            http_response_code(422);
            die('Pedido inválido.');
        }

        $cancelado = (new Compra())->cancelarPedidoCliente(
            (int)$pedidoId,
            (int)$perfilCliente['id']
        );
        $_SESSION['mensagem_pedidos'] = $cancelado
            ? 'Pedido cancelado. Os itens voltaram ao estoque.'
            : 'Este pedido não pode ser cancelado. Apenas pedidos aguardando pagamento podem ser cancelados.';
        $this->ir('meusPedidos');
    }

    public function login(): void
    {
        $this->validarPost();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');
        $conta = (new ClienteConta())->buscarPorEmail($email);
        if (
            !$conta ||
            (int)$conta['ativo'] !== 1 ||
            !in_array($conta['perfil'], ['cliente', 'admin', 'vendedor'], true) ||
            !password_verify($senha, $conta['senha'])
        ) {
            $_SESSION['mensagem_conta'] = 'E-mail ou senha inválidos.';
            $this->ir('index');
        }

        if ($conta['perfil'] === 'cliente') {
            $this->iniciarSessaoCliente((int)$conta['id']);
            $this->irLoja();
        }

        $this->iniciarSessaoEquipe($conta);
        header('Location: index.php?controller=auth&action=dashboard');
        exit;
    }

    public function criar(): void
    {
        $this->validarPost();
        $nome = trim($_POST['nome'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');
        $confirmacao = (string)($_POST['confirmacao_senha'] ?? '');
        if (
            $nome === '' || strlen($nome) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 ||
            strlen($senha) < 8 || $senha !== $confirmacao
        ) {
            $_SESSION['mensagem_conta'] = 'Confira os dados. A senha precisa ter ao menos 8 caracteres e as senhas devem ser iguais.';
            $this->ir('cadastro');
        }

        $modelo = new ClienteConta();
        if ($modelo->buscarPorEmail($email)) {
            $_SESSION['mensagem_conta'] = 'Este e-mail já possui uma conta. Entre com ela ou use outro e-mail.';
            $this->ir('cadastro');
        }

        try {
            $usuarioId = $modelo->cadastrar($nome, $email, $senha);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $_SESSION['mensagem_conta'] = 'Este e-mail já possui uma conta. Entre com ela ou use outro e-mail.';
            $this->ir('cadastro');
        }
        $this->iniciarSessaoCliente($usuarioId);
        $this->irLoja();
    }

    public function sair(): void
    {
        unset(
            $_SESSION['cliente_usuario_id'],
            $_SESSION['cliente_nome'],
            $_SESSION['cliente_email'],
            $_SESSION['cliente_perfil'],
            $_SESSION['carrinho'],
            $_SESSION['frete_estimado'],
            $_SESSION['cep_frete']
        );
        session_regenerate_id(true);
        $this->ir('index');
    }

    public static function token(): string
    {
        if (empty($_SESSION['csrf_cliente'])) {
            $_SESSION['csrf_cliente'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_cliente'];
    }

    private function perfilAtual(): ?array
    {
        $id = (int)($_SESSION['cliente_usuario_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $perfil = (new ClienteConta())->buscarPerfil($id);
        if (!$perfil) {
            unset($_SESSION['cliente_usuario_id'], $_SESSION['cliente_nome'], $_SESSION['cliente_email']);
            return null;
        }
        $_SESSION['cliente_nome'] = $perfil['nome'];
        $_SESSION['cliente_email'] = $perfil['email'];
        return $perfil;
    }

    private function iniciarSessaoCliente(int $usuarioId): void
    {
        unset($_SESSION['usuario_id'], $_SESSION['perfil'], $_SESSION['nome']);
        if (
            isset($_SESSION['cliente_usuario_id']) &&
            (int)$_SESSION['cliente_usuario_id'] !== $usuarioId
        ) {
            unset($_SESSION['carrinho'], $_SESSION['frete_estimado'], $_SESSION['cep_frete']);
        }
        session_regenerate_id(true);
        $_SESSION['cliente_usuario_id'] = $usuarioId;
        $_SESSION['cliente_perfil'] = 'cliente';
        $_SESSION['csrf_cliente'] = bin2hex(random_bytes(32));
    }

    private function iniciarSessaoEquipe(array $conta): void
    {
        unset(
            $_SESSION['cliente_usuario_id'],
            $_SESSION['cliente_nome'],
            $_SESSION['cliente_email'],
            $_SESSION['cliente_perfil'],
            $_SESSION['carrinho'],
            $_SESSION['frete_estimado'],
            $_SESSION['cep_frete']
        );
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int)$conta['id'];
        $_SESSION['perfil'] = $conta['perfil'];
        $_SESSION['nome'] = $conta['nome'];
    }

    private function validarPost(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !hash_equals(self::token(), $_POST['csrf'] ?? '')
        ) {
            http_response_code(403);
            die('Solicitação inválida. Atualize a página e tente novamente.');
        }
    }

    private function ir(string $action): void
    {
        header('Location: index.php?controller=cliente&action=' . rawurlencode($action));
        exit;
    }

    private function irLoja(): void
    {
        header('Location: index.php');
        exit;
    }
}

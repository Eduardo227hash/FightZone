<?php
require_once __DIR__ . '/../models/produto.php';
require_once __DIR__ . '/../models/categoria.php';

class ProdutoController
{
    public function index(): void
    {
        $this->check();

        $produtoModel = new Produto();
        if (empty($_SESSION['csrf_estoque'])) {
            $_SESSION['csrf_estoque'] = bin2hex(random_bytes(32));
        }
        $csrfEstoque = $_SESSION['csrf_estoque'];
        $produtos = $produtoModel->listarTodos();
        $categorias = (new Categoria())->listarAtivas();
        $editar = null;
        if (isset($_GET['id'])) {
            $editar = $produtoModel->buscarPorId((int)$_GET['id']);
        }

        require_once __DIR__ . '/../views/produtos.php';
    }

    public function salvar(): void
    {
        $this->check();
        $this->onlyAdmin();
        $this->validarCsrf();

        $id = (int)($_POST['id'] ?? 0);

        $nome = trim($_POST['nome_produto'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $marca = trim($_POST['marca'] ?? '');
        $preco = (float)($_POST['preco'] ?? 0);
        $estoque = (int)($_POST['estoque'] ?? 0);
        $peso = (float)($_POST['peso'] ?? 0.5);
        $altura = (float)($_POST['altura'] ?? 10);
        $largura = (float)($_POST['largura'] ?? 10);
        $comprimento = (float)($_POST['comprimento'] ?? 10);

        if ($nome === '' || $categoriaId <= 0 || $marca === '' || $preco <= 0 ||
            $estoque < 0 || $peso <= 0 || $altura <= 0 || $largura <= 0 || $comprimento <= 0) {
            http_response_code(422);
            die("Confira nome, categoria, SKU, preço, estoque e peso.");
        }

        $produtoModel = new Produto();
        $imagemPath = null;

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK || $_FILES['imagem']['size'] > 5 * 1024 * 1024) {
                http_response_code(422);
                die("A imagem deve ter até 5 MB.");
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['imagem']['tmp_name']);
            $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($extensoes[$mime])) {
                http_response_code(422);
                die("Formato de imagem não permitido. Use JPG, PNG ou WebP.");
            }
            $pasta = __DIR__ . '/../uploads/';
            if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
                throw new RuntimeException("Não foi possível criar a pasta de imagens.");
            }
            $nomeArquivo = bin2hex(random_bytes(12)) . '.' . $extensoes[$mime];
            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $pasta . $nomeArquivo)) {
                throw new RuntimeException("Não foi possível salvar a imagem enviada.");
            }
            $imagemPath = 'uploads/' . $nomeArquivo;
        }

        if ($id > 0) {
            $produtoModel->atualizar($id, $nome, $descricao, $categoriaId, $marca, $preco, $estoque, $peso, $altura, $largura, $comprimento, $imagemPath);
        } else {
            $produtoModel->inserir($nome, $descricao, $categoriaId, $marca, $preco, $estoque, $peso, $altura, $largura, $comprimento, $imagemPath);
        }
        header("Location: index.php?controller=produto&action=index");
        exit;
    }

    public function deletar(): void
    {
        $this->check();
        $this->onlyAdmin();
        $this->validarCsrf();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            die("ID inválido.");
        }

        $produtoModel = new Produto();
        $produtoModel->deletar($id);

        header("Location: index.php?controller=produto&action=index");
        exit;
    }

    private function check(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: index.php?controller=auth&action=form");
            exit;
        }
    }

    private function onlyAdmin(): void
    {
        if (($_SESSION['perfil'] ?? '') !== 'admin') {
            die("Acesso negado.");
        }
    }

    private function validarCsrf(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !hash_equals($_SESSION['csrf_estoque'] ?? '', $_POST['csrf'] ?? '')
        ) {
            http_response_code(403);
            die("Solicitação inválida. Atualize a página e tente novamente.");
        }
    }
}
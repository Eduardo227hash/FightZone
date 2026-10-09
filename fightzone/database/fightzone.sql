CREATE DATABASE IF NOT EXISTS fightzone
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE fightzone;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('admin', 'vendedor', 'cliente') NOT NULL DEFAULT 'cliente',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS produtos (
  id INT NOT NULL AUTO_INCREMENT,
  categoria_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  slug VARCHAR(170) NOT NULL,
  marca VARCHAR(80) NOT NULL,
  descricao TEXT NULL,
  preco DECIMAL(10,2) NOT NULL,
  peso DECIMAL(7,2) NOT NULL DEFAULT 0.50,
  altura DECIMAL(7,2) NOT NULL DEFAULT 10,
  largura DECIMAL(7,2) NOT NULL DEFAULT 10,
  comprimento DECIMAL(7,2) NOT NULL DEFAULT 10,
  estoque INT NOT NULL DEFAULT 0,
  imagem VARCHAR(255) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_produtos_slug (slug),
  KEY idx_produtos_categoria (categoria_id),
  CONSTRAINT fk_produtos_categorias FOREIGN KEY (categoria_id) REFERENCES categorias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  nome VARCHAR(100) NOT NULL,
  cpf VARCHAR(14) NULL,
  telefone VARCHAR(20) NULL,
  cep VARCHAR(9) NULL,
  logradouro VARCHAR(150) NULL,
  numero VARCHAR(20) NULL,
  complemento VARCHAR(100) NULL,
  bairro VARCHAR(100) NULL,
  cidade VARCHAR(100) NULL,
  uf CHAR(2) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clientes_usuario (usuario_id),
  CONSTRAINT fk_clientes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  frete DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  status ENUM('aguardando','pago','separacao','enviado','entregue','cancelado') NOT NULL DEFAULT 'aguardando',
  pagamento ENUM('pix','cartao','boleto') NOT NULL,
  cep_entrega VARCHAR(9) NULL,
  endereco_entrega TEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pedidos_usuario (usuario_id),
  CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS itens_pedido (
  id INT NOT NULL AUTO_INCREMENT,
  pedido_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_itens_pedido_pedido (pedido_id),
  KEY idx_itens_pedido_produto (produto_id),
  CONSTRAINT fk_itens_pedido_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id),
  CONSTRAINT fk_itens_pedido_produto FOREIGN KEY (produto_id) REFERENCES produtos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimentos_estoque (
  id INT NOT NULL AUTO_INCREMENT,
  produto_id INT NOT NULL,
  tipo ENUM('entrada','saida','ajuste') NOT NULL,
  quantidade INT NOT NULL,
  observacao VARCHAR(255) NULL,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_movimentos_produto (produto_id),
  CONSTRAINT fk_movimentos_produto FOREIGN KEY (produto_id) REFERENCES produtos (id),
  CONSTRAINT fk_movimentos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notas_fiscais (
  id INT NOT NULL AUTO_INCREMENT,
  pedido_id INT NOT NULL,
  numero VARCHAR(50) NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_notas_pedido (pedido_id),
  UNIQUE KEY uq_notas_numero (numero),
  CONSTRAINT fk_notas_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS codigo_acesso CHAR(48) NULL,
  ADD COLUMN IF NOT EXISTS cliente_nome VARCHAR(160) NULL,
  ADD COLUMN IF NOT EXISTS cliente_email VARCHAR(190) NULL,
  ADD COLUMN IF NOT EXISTS cliente_documento VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS cliente_telefone VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS status_pagamento VARCHAR(30) NULL;

ALTER TABLE itens_pedido
  ADD COLUMN IF NOT EXISTS produto_nome VARCHAR(150) NULL;

INSERT INTO usuarios (nome, email, senha, perfil, ativo, created_at)
VALUES (
  'Gerente FightZone',
  'GerenteFZ@gmail.com',
  '$2y$10$unCgWEfRm2YuJV1qA0EtBu3fAoG4ooikUO0lDoyrFpaw1DvBaH9jG',
  'admin',
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  senha = VALUES(senha),
  perfil = VALUES(perfil),
  ativo = VALUES(ativo);

INSERT IGNORE INTO categorias (nome, slug, ativo) VALUES
  ('Luvas', 'luvas', 1),
  ('Bandagens', 'bandagens', 1),
  ('Proteção', 'protecao', 1),
  ('Roupas', 'roupas', 1),
  ('Acessórios', 'acessorios', 1),
  ('Sacos e aparadores', 'sacos-aparadores', 1);

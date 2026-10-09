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

INSERT INTO produtos
  (categoria_id, nome, slug, marca, descricao, preco, peso, altura, largura, comprimento, estoque, imagem, ativo, created_at)
SELECT
  c.id,
  s.nome,
  CONCAT('SEED-FZ-', SHA2(s.imagem, 256)),
  s.marca,
  s.descricao,
  s.preco,
  s.peso,
  s.altura,
  s.largura,
  s.comprimento,
  s.estoque,
  s.imagem,
  1,
  NOW()
FROM (
  SELECT 'luvas' AS categoria, 'Luva Title Boxing' AS nome,
         'Title Boxing' AS marca, 'Luva Sparring 16OZ Title Boxing' AS descricao,
         699.90 AS preco, 0.75 AS peso, 18 AS altura, 16 AS largura, 36 AS comprimento, 12 AS estoque,
         'uploads/TitleVermelha.png' AS imagem
  UNION ALL SELECT 'sacos-aparadores', 'Saco de Pancadas Gorilla',
         'Gorilla', 'Saco de pancadas 1,90 M', 899.90, 25.00, 100, 35, 35, 5,
         'uploads/saco-gorilla.png'
  UNION ALL SELECT 'luvas', 'Luva No Boxing No Life',
         'No Boxing No Life', 'Luva Sparring 16OZ No Boxing No Life', 999.90, 0.75, 18, 16, 36, 8,
         'uploads/Luva-noboxing-preta.png'
  UNION ALL SELECT 'roupas', 'Camisa Boxing Club',
         'Boxing Club', 'Camisa Oversized Boxing Club M', 129.90, 0.25, 4, 28, 36, 15,
         'uploads/camisa.png'
  UNION ALL SELECT 'protecao', 'Bucal Maximum',
         'Maximum', 'Bucal simples Maximum', 79.90, 0.10, 5, 8, 12, 16,
         'uploads/bucal-maximum.png'
  UNION ALL SELECT 'luvas', 'Luva No Boxing No Life',
         'No Boxing No Life', 'Luva Sparring 16OZ No Boxing No Life', 1000.00, 0.75, 18, 16, 36, 8,
         'uploads/noboxing-azul.png'
  UNION ALL SELECT 'luvas', 'Luva Maximum',
         'Maximum', 'Luva Sparring 16OZ Maximum', 529.90, 0.75, 18, 16, 36, 10,
         'uploads/Luva-maximumpreta.png'
  UNION ALL SELECT 'sacos-aparadores', 'Aparadores de Foco Title (Par)',
         'Title Boxing', 'Par de aparadores para treino de precisão e velocidade.', 459.90, 1.20, 8, 22, 28, 6,
         'uploads/Aparador-title.png'
  UNION ALL SELECT 'luvas', 'Luva Cleto Reyes',
         'Reyes', 'Luva Sparring 16OZ Cleto Reyes', 849.90, 0.75, 18, 16, 36, 6,
         'uploads/CletoReyes-marrom.webp'
  UNION ALL SELECT 'protecao', 'Bucal Spark Preto',
         'Spark', 'Bucal simples Spark preto', 89.90, 0.10, 5, 8, 12, 15,
         'uploads/bucal-spark.png'
  UNION ALL SELECT 'bandagens', 'Bandagem Elástica',
         'Spark', 'Bandagem Maximum 5M', 69.90, 0.20, 6, 10, 12, 20,
         'uploads/bandagemspark.png'
  UNION ALL SELECT 'protecao', 'Coquilha de Proteção Title',
         'Title Boxing', 'Coquilha Tamanho M', 249.90, 0.35, 12, 20, 25, 10,
         'uploads/coquilha-title.png'
) AS s
JOIN categorias AS c ON c.slug = s.categoria
WHERE NOT EXISTS (
  SELECT 1 FROM produtos AS existente WHERE existente.imagem = s.imagem
);

UPDATE produtos AS p
JOIN (
  SELECT 'luvas' AS categoria, 'Luva Title Boxing' AS nome, 'Title Boxing' AS marca,
         'Luva Sparring 16OZ Title Boxing' AS descricao, 699.90 AS preco, 0.75 AS peso,
         18 AS altura, 16 AS largura, 36 AS comprimento, 12 AS estoque,
         'uploads/TitleVermelha.png' AS imagem
  UNION ALL SELECT 'sacos-aparadores', 'Saco de Pancadas Gorilla', 'Gorilla', 'Saco de pancadas 1,90 M', 899.90, 25.00, 100, 35, 35, 5,
         'uploads/saco-gorilla.png'
  UNION ALL SELECT 'luvas', 'Luva No Boxing No Life', 'No Boxing No Life', 'Luva Sparring 16OZ No Boxing No Life', 999.90, 0.75, 18, 16, 36, 8,
         'uploads/Luva-noboxing-preta.png'
  UNION ALL SELECT 'roupas', 'Camisa Boxing Club', 'Boxing Club', 'Camisa Oversized Boxing Club M', 129.90, 0.25, 4, 28, 36, 15,
         'uploads/camisa.png'
  UNION ALL SELECT 'protecao', 'Bucal Maximum', 'Maximum', 'Bucal simples Maximum', 79.90, 0.10, 5, 8, 12, 16,
         'uploads/bucal-maximum.png'
  UNION ALL SELECT 'luvas', 'Luva No Boxing No Life', 'No Boxing No Life', 'Luva Sparring 16OZ No Boxing No Life', 1000.00, 0.75, 18, 16, 36, 8,
         'uploads/noboxing-azul.png'
  UNION ALL SELECT 'luvas', 'Luva Maximum', 'Maximum', 'Luva Sparring 16OZ Maximum', 529.90, 0.75, 18, 16, 36, 10,
         'uploads/Luva-maximumpreta.png'
  UNION ALL SELECT 'sacos-aparadores', 'Aparadores de Foco Title (Par)', 'Title Boxing', 'Par de aparadores para treino de precisão e velocidade.', 459.90, 1.20, 8, 22, 28, 6,
         'uploads/Aparador-title.png'
  UNION ALL SELECT 'luvas', 'Luva Cleto Reyes', 'Reyes', 'Luva Sparring 16OZ Cleto Reyes', 849.90, 0.75, 18, 16, 36, 6,
         'uploads/CletoReyes-marrom.webp'
  UNION ALL SELECT 'protecao', 'Bucal Spark Preto', 'Spark', 'Bucal simples Spark', 89.90, 0.10, 5, 8, 12, 15,
         'uploads/bucal-spark.png'
  UNION ALL SELECT 'bandagens', 'Bandagem Elástica para Boxe', 'Spark', 'Bandagem maximum 5M', 69.90, 0.20, 6, 10, 12, 20,
         'uploads/bandagemspark.png'
  UNION ALL SELECT 'protecao', 'Coquilha de Proteção Title', 'Title Boxing', 'Coquilha Tamnho M', 249.90, 0.35, 12, 20, 25, 10,
         'uploads/coquilha-title.png'
) AS s ON s.imagem = p.imagem
JOIN categorias AS c ON c.slug = s.categoria
SET p.categoria_id = c.id,
    p.nome = s.nome,
    p.marca = s.marca,
    p.descricao = s.descricao,
    p.preco = s.preco,
    p.peso = s.peso,
    p.altura = s.altura,
    p.largura = s.largura,
    p.comprimento = s.comprimento,
    p.estoque = s.estoque,
    p.slug = CONCAT('FZ-', LPAD(p.id, 6, '0')),
    p.ativo = 1;

DELETE p
FROM produtos AS p
LEFT JOIN itens_pedido AS i ON i.produto_id = p.id
LEFT JOIN movimentos_estoque AS m ON m.produto_id = p.id
WHERE p.imagem IN (
  'uploads/1d934e6bafb674932a3d0889.png',
  'uploads/616cf0193aa5e2a192688d27.png',
  'uploads/53fd377013d0d1e4f5fb55e5.png'
)
AND i.id IS NULL
AND m.id IS NULL;

UPDATE produtos
SET slug = CONCAT('FZ-', LPAD(id, 6, '0')),
    imagem = NULL,
    ativo = 0
WHERE imagem IN (
  'uploads/1d934e6bafb674932a3d0889.png',
  'uploads/616cf0193aa5e2a192688d27.png',
  'uploads/53fd377013d0d1e4f5fb55e5.png'
);

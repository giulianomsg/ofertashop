-- Estrutura do Banco de Dados Oferta Shop

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  senha_hash VARCHAR(255)
);

CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100)
);

CREATE TABLE ofertas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255),
  descricao TEXT,
  imagem_url VARCHAR(255),
  preco_original DECIMAL(10,2),
  preco_atual DECIMAL(10,2),
  link_afiliado VARCHAR(255),
  categoria_id INT,
  ativo BOOLEAN DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

CREATE TABLE estatisticas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  oferta_id INT,
  cliques INT DEFAULT 0,
  visualizacoes INT DEFAULT 0,
  FOREIGN KEY (oferta_id) REFERENCES ofertas(id)
);

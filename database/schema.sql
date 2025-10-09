-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 09/10/2025 às 13:14
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u197364123_ofertashop`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admins`
--

INSERT INTO `admins` (`id`, `nome`, `email`, `senha_hash`) VALUES
(1, 'Giuliano Moretti', 'giulianomsg@gmail.com', '$2y$10$46dv7tF4/F8bAyQSDECsY.OdUk.9rSreZU69VTp7Pf3464CIi6P0C'),
(2, 'Amanda Sagres', 'amandacsagres@gmail.com', '$2y$10$bxICzRng6BpxK5uZRswI0.5HTjGtNuF3PuVz7HobSUEqlDkxOYunS');

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL,
  `oferta_id` int(11) NOT NULL,
  `nota` int(11) NOT NULL CHECK (`nota` between 1 and 5),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `avaliacoes`
--

INSERT INTO `avaliacoes` (`id`, `oferta_id`, `nota`, `created_at`) VALUES
(1, 3, 5, '2025-10-09 12:50:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`) VALUES
(1, 'Eletrônicos'),
(2, 'Casa'),
(3, 'Vestuário');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estatisticas`
--

CREATE TABLE `estatisticas` (
  `id` int(11) NOT NULL,
  `oferta_id` int(11) DEFAULT NULL,
  `cliques` int(11) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `imagens_produto`
--

CREATE TABLE `imagens_produto` (
  `id` int(11) NOT NULL,
  `oferta_id` int(11) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `imagens_produto`
--

INSERT INTO `imagens_produto` (`id`, `oferta_id`, `caminho`, `created_at`) VALUES
(1, 2, 'uploads/produtos/img_68e73d035f2764.58409066.jpg', '2025-10-09 04:41:39'),
(2, 2, 'uploads/produtos/img_68e73d035f7b70.31973789.jpg', '2025-10-09 04:41:39'),
(3, 2, 'uploads/produtos/img_68e73d035fc6c0.59658730.jpg', '2025-10-09 04:41:39'),
(4, 2, 'uploads/produtos/img_68e73d03600ac0.56501724.jpg', '2025-10-09 04:41:39'),
(5, 2, 'uploads/produtos/img_68e73d03604680.91029178.jpg', '2025-10-09 04:41:39'),
(9, 3, 'uploads/produtos/img_68e74e2fb0a565.10007963.webp', '2025-10-09 05:54:55'),
(10, 3, 'uploads/produtos/img_68e74e48510825.48146921.webp', '2025-10-09 05:55:20'),
(11, 3, 'uploads/produtos/img_68e74e5214c938.23369989.webp', '2025-10-09 05:55:30'),
(12, 3, 'uploads/produtos/img_68e74e521522c3.51378295.webp', '2025-10-09 05:55:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ofertas`
--

CREATE TABLE `ofertas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `descricao_resumida` text NOT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `preco_original` decimal(10,2) DEFAULT NULL,
  `preco_atual` decimal(10,2) DEFAULT NULL,
  `link_afiliado` varchar(255) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `programa_id` int(11) DEFAULT NULL,
  `acessos` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ofertas`
--

INSERT INTO `ofertas` (`id`, `titulo`, `descricao`, `descricao_resumida`, `imagem_url`, `preco_original`, `preco_atual`, `link_afiliado`, `categoria_id`, `ativo`, `created_at`, `programa_id`, `acessos`) VALUES
(2, 'Echo Pop (Geração mais recente) | Smart speaker compacto com som envolvente e Alexa | Cor Preta', 'CONHEÇA O ECHO POP - Este smart speaker compacto com Alexa conta com som de qualidade e é perfeito para quartos e espaços pequenos. Pequeno o suficiente para combinar com o ambiente, mas poderoso o bastante para se destacar.\r\nCONTROLE A MÚSICA POR VOZ - Peça para Alexa tocar músicas e podcasts nas suas plataformas preferidas, como Amazon Music, Apple Music, Spotify, Deezer e outras. Conecte-o também por Bluetooth e ouça músicas do seu celular em todo o ambiente.\r\nTORNE SUA CASA INTELIGENTE - Controle dispositivos de casa inteligente compatíveis, como plugues ou lâmpadas inteligentes, com sua voz ou pelo aplicativo Alexa. Crie rotinas para ligar as luzes ao pôr do sol ou para apagá-las automaticamente na hora de dormir.\r\nSUA VIDA MAIS FÁCIL - Peça para Alexa definir timers, informar a previsão do tempo, ler as notícias, comprar produtos, realizar chamadas, responder às suas perguntas e muito mais.\r\nALEXA TEM SKILLS - Com milhares de Skills, Alexa pode te ajudar a fazer mais coisas em menos tempo ao tocar músicas relaxantes ou acompanhar as últimas notícias.\r\nSOBRE A BARRA DE LUZ- Alexa só começa a escutar quando seu dispositivo detecta a palavra “Alexa” e sua barra de luz fica azul.\r\nDESENVOLVIDO PARA PROTEGER A SUA PRIVACIDADE - A Amazon não vende informações pessoais de clientes. Este dispositivo foi desenvolvido com várias camadas de controles de privacidade, incluindo o botão de desligar o microfone.\r\nDESENVOLVIDO PARA A SUSTENTABILIDADE - Nós pensamos na sustentabilidade ao desenvolver este dispositivo com 100% do tecido feito com fios de poliéster reciclados pós-consumo e 80% de alumínio reciclado. 99% da embalagem deste dispositivo é feita de materiais à base de fibra de madeira de florestas geridas de forma responsável ou fontes recicladas.', 'CONHEÇA O ECHO POP - Este smart speaker compacto com Alexa conta com som de qualidade e é perfeito para quartos e espaços pequenos. Pequeno o suficiente para combinar com o ambiente, mas poderoso o bastante para se destacar.', 'https://m.media-amazon.com/images/I/8120tVg3AcL._AC_SL1500_.jpg', 350.00, 299.00, 'https://www.amazon.com.br/Echo-Pop-Cor-Preta/dp/B09WXVH7WK?ref=dlx_prime_dg_dcl_B09WXVH7WK_dt_sl7_ab_pi&pf_rd_r=GF5QSZHBBPBN9EPM45V4&pf_rd_p=de67dcda-a4a9-407e-87da-0f98444cabab&th=1', 1, 1, '2025-10-09 04:05:33', 1, 0),
(3, 'Oxímetro Digital de Dedo Batimentos e Saturação LK87 LED Aparelho Portátil FingerTip e Pilhas', 'Oxímetro Digital de Dedo Fingertip – Seu Parceiro no Monitoramento da Saúde!\r\n\r\nMantenha sua saúde sempre sob controle com o Oxímetro Digital de Dedo Fingertip, um dispositivo essencial para medir saturação de oxigênio (SpO₂) e frequência cardíaca com precisão e rapidez. Seja para uso doméstico, esportivo ou profissional, este oxímetro oferece tecnologia confiável e fácil de usar.\r\n\r\nPor que escolher o Oxímetro Fingertip?\r\n\r\n✅ Resultados Rápidos e Precisos – Obtenha leituras confiáveis em segundos.\r\n✅ Display LED de Alta Definição – Fácil leitura em qualquer ambiente, até no escuro.\r\n✅ Leve e Portátil – Carregue para onde quiser e monitore sua saúde a qualquer momento.\r\n✅ Funcionamento Simples e Intuitivo – Basta encaixar no dedo e obter os resultados sem complicação.\r\n✅ Alta Durabilidade – Construído com materiais de qualidade para longa vida útil.\r\n✅ Seguro e Confiável – Ideal para atletas, idosos, pacientes respiratórios e qualquer pessoa preocupada com o bem-estar.\r\n\r\n🔹 Acompanha manual do usuário para uso correto e eficiente.\r\n🛡️ Garantia do vendedor: 30 dias – sua compra protegida!\r\n⚡ Não arrisque sua saúde! Tenha um monitoramento confiável sempre à mão. Adquira já o seu!\r\n\r\nPalavras chave: Oxímetro digital, oxímetro portátil, medidor de oxigênio, saturação de oxigênio, oxímetro de dedo, monitor de SpO2, frequência cardíaca, pulso e oxigênio, saúde respiratória, oxigenação do sangue, aparelho de oximetria, monitor portátil de oxigênio, dispositivo médico portátil, oxímetro preciso, monitoramento em casa.', 'Oxímetro Digital de Dedo Fingertip – Seu Parceiro no Monitoramento da Saúde!\r\nMantenha sua saúde sempre sob controle com o Oxímetro Digital de Dedo Fingertip, um dispositivo essencial para medir saturação de oxigênio (SpO₂) e frequência cardíaca co', 'uploads/produtos/img_68e74e2fb0a565.10007963.webp', 60.00, 22.90, 'https://s.shopee.com.br/7KoDaAjLBA', 1, 1, '2025-10-09 05:54:55', 2, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `programas_afiliados`
--

CREATE TABLE `programas_afiliados` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `icone_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `programas_afiliados`
--

INSERT INTO `programas_afiliados` (`id`, `nome`, `icone_url`) VALUES
(1, 'Amazon', 'uploads/afiliados/icone_68e743196d1026.52432499.png'),
(2, 'Shopee', 'uploads/afiliados/68e74644ab284.png');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oferta_id` (`oferta_id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `estatisticas`
--
ALTER TABLE `estatisticas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oferta_id` (`oferta_id`);

--
-- Índices de tabela `imagens_produto`
--
ALTER TABLE `imagens_produto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oferta_id` (`oferta_id`);

--
-- Índices de tabela `ofertas`
--
ALTER TABLE `ofertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `programa_id` (`programa_id`);

--
-- Índices de tabela `programas_afiliados`
--
ALTER TABLE `programas_afiliados`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `estatisticas`
--
ALTER TABLE `estatisticas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `imagens_produto`
--
ALTER TABLE `imagens_produto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `ofertas`
--
ALTER TABLE `ofertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `programas_afiliados`
--
ALTER TABLE `programas_afiliados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`oferta_id`) REFERENCES `ofertas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `estatisticas`
--
ALTER TABLE `estatisticas`
  ADD CONSTRAINT `estatisticas_ibfk_1` FOREIGN KEY (`oferta_id`) REFERENCES `ofertas` (`id`);

--
-- Restrições para tabelas `imagens_produto`
--
ALTER TABLE `imagens_produto`
  ADD CONSTRAINT `imagens_produto_ibfk_1` FOREIGN KEY (`oferta_id`) REFERENCES `ofertas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ofertas`
--
ALTER TABLE `ofertas`
  ADD CONSTRAINT `ofertas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `ofertas_ibfk_2` FOREIGN KEY (`programa_id`) REFERENCES `programas_afiliados` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

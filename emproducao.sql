-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 15/08/2025 às 17:36
-- Versão do servidor: 8.0.43-0ubuntu0.24.04.1
-- Versão do PHP: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `inventario_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int NOT NULL,
  `chave` varchar(255) NOT NULL,
  `valor` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave`, `valor`) VALUES
(1, 'cabecalho_padrao_pdf', 'Digite o texto do cabeçalho de seu relatório aqui.\r\n'),
(2, 'logo_path', 'uploads/logo.png');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens`
--

CREATE TABLE `itens` (
  `id` int NOT NULL,
  `processo_documento` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `patrimonio_novo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `patrimonio_secundario` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `local_id` int NOT NULL,
  `responsavel_id` int NOT NULL,
  `estado` enum('Em uso','Ocioso','Recuperável','Inservível') COLLATE utf8mb4_general_ci NOT NULL,
  `observacao` text COLLATE utf8mb4_general_ci,
  `descricao_detalhada` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantidade` int DEFAULT '1',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_anterior_id` int DEFAULT NULL,
  `empenho` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_emissao_empenho` date DEFAULT NULL,
  `fornecedor` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cnpj_cpf_fornecedor` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cnpj_fornecedor` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `valor_nf` decimal(10,2) DEFAULT NULL,
  `nd_nota_despesa` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unidade_medida` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `nota_fiscal_documento` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_entrada_aceitacao` date DEFAULT NULL,
  `status_confirmacao` enum('Pendente','Confirmado','Nao Confirmado','Movimento Desfeito') COLLATE utf8mb4_general_ci DEFAULT 'Pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `itens`
--

INSERT INTO `itens` (`id`, `processo_documento`, `nome`, `patrimonio_novo`, `patrimonio_secundario`, `local_id`, `responsavel_id`, `estado`, `observacao`, `descricao_detalhada`, `numero_serie`, `quantidade`, `data_cadastro`, `usuario_anterior_id`, `empenho`, `data_emissao_empenho`, `fornecedor`, `cnpj_cpf_fornecedor`, `cnpj_fornecedor`, `categoria`, `valor_nf`, `nd_nota_despesa`, `unidade_medida`, `valor`, `nota_fiscal_documento`, `data_entrada_aceitacao`, `status_confirmacao`) VALUES
(2, NULL, 'Monitor', '12858/2014', '12858/2014', 3, 4, 'Em uso', 'Monitor de uso', NULL, NULL, 1, '2025-08-04 19:48:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente'),
(4, NULL, 'Frigobar consul', '2806/2015', '2806/2015', 1, 2, 'Em uso', '', NULL, NULL, 1, '2025-08-05 18:06:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nao Confirmado'),
(5, NULL, 'Gaveteiro', '1235/2025', '2135/2025', 1, 2, 'Em uso', '', NULL, NULL, 1, '2025-08-05 18:27:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente'),
(10, NULL, 'teste', '12345678', '123456', 7, 2, 'Em uso', 'teste', NULL, NULL, 1, '2025-08-06 15:57:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente'),
(11, NULL, 'Monitor', 'S/P', '1903/2014', 1, 2, 'Em uso', '', NULL, NULL, 1, '2025-08-06 17:10:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nao Confirmado'),
(12, NULL, 'Notebook Lenovo Thinkpad', '121491', '', 1, 5, 'Em uso', '', NULL, NULL, 1, '2025-08-08 11:23:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente'),
(13, NULL, 'COMPUTADOR HP ELITEDEK', '99105', '', 5, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-12 19:37:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente'),
(14, NULL, 'MONITOR HP V206HZ', '71815', '', 4, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-12 19:38:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente'),
(15, NULL, 'MONITOR POSITIVO', '108505', '', 4, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-12 19:39:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente'),
(16, NULL, 'MICROCUMPUTADOR', '121467', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(17, NULL, 'MICROCUMPUTADOR', '121468', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(18, NULL, 'MICROCUMPUTADOR', '121469', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(19, NULL, 'MICROCUMPUTADOR', '121470', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(20, NULL, 'MICROCUMPUTADOR', '121471', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(21, NULL, 'MICROCUMPUTADOR', '121472', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(22, NULL, 'MICROCUMPUTADOR', '121473', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(23, NULL, 'MICROCUMPUTADOR', '121474', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(24, NULL, 'MICROCUMPUTADOR', '121475', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(25, NULL, 'MICROCUMPUTADOR', '121476', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(26, NULL, 'MICROCUMPUTADOR', '121477', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(27, NULL, 'MICROCUMPUTADOR', '121478', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(28, NULL, 'MICROCUMPUTADOR', '121479', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(29, NULL, 'MICROCUMPUTADOR', '121480', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(30, NULL, 'MICROCUMPUTADOR', '121481', NULL, 24, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(31, NULL, 'MICROCUMPUTADOR', '121482', NULL, 2, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(32, NULL, 'MICROCUMPUTADOR', '121483', NULL, 2, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente'),
(33, NULL, 'MICROCUMPUTADOR', '121484', NULL, 2, 8, 'Em uso', '', NULL, NULL, 1, '2025-08-13 11:31:03', NULL, '2024NE001173', '2024-12-31', 'LIDER NOTEBOOKS COMERCIO E SERVICOS LTDA', NULL, '12.477.490/0002-81', '0', 121467.00, '449052', 'unidade', 3980.00, NULL, NULL, 'Pendente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `locais`
--

CREATE TABLE `locais` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('aprovado','pendente','rejeitado') COLLATE utf8mb4_general_ci DEFAULT 'aprovado',
  `solicitado_por` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `locais`
--

INSERT INTO `locais` (`id`, `nome`, `status`, `solicitado_por`) VALUES
(1, 'Seção de Tecnologia da Informação (STI)', 'aprovado', NULL),
(2, 'Seção de Tecnologia da Informação - Anexo (STI-A)', 'aprovado', NULL),
(3, 'Seção de Apoio ao Campo (SAAC)', 'aprovado', NULL),
(4, 'DIREÇÃO GERAL E ACADÊMICA', 'aprovado', NULL),
(5, 'Sala 01 Bloco 01', 'aprovado', NULL),
(6, 'Sala 02 Bloco 01', 'aprovado', NULL),
(7, 'Sala 03 Bloco 01', 'aprovado', NULL),
(8, 'Sala 04 Bloco 01', 'aprovado', NULL),
(9, 'Sala 05 Bloco 01', 'aprovado', NULL),
(10, 'Sala 06 Bloco 01', 'aprovado', NULL),
(11, 'Sala 07 Bloco 01', 'aprovado', NULL),
(12, 'Sala 08 Bloco 01', 'aprovado', NULL),
(13, 'Sala 09 Bloco 01', 'aprovado', NULL),
(14, 'Sala 10 Bloco 01', 'aprovado', NULL),
(15, 'Sala 11 Bloco 01', 'aprovado', NULL),
(16, 'Sala 12 Bloco 01', 'aprovado', NULL),
(17, 'Sala 13 Bloco 01', 'aprovado', NULL),
(18, 'Sala 14 Bloco 01', 'aprovado', NULL),
(19, 'Sala 15 Bloco 01', 'aprovado', NULL),
(20, 'Sala 02 Bloco 02', 'aprovado', NULL),
(21, 'Sala 01 Bloco 02', 'aprovado', NULL),
(22, 'Sala 03 Bloco 02', 'aprovado', NULL),
(23, 'Almoxarifado e Patrimônio', 'aprovado', NULL),
(24, 'Almoxarifado e Patrimônio (GALPÃO)', 'aprovado', NULL),
(25, 'Processo de Desfazimento 23082.017218/2020-93 (GALPAO)', 'aprovado', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `local_origem_id` int NOT NULL,
  `local_destino_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_anterior_id` int DEFAULT NULL,
  `status_confirmacao` enum('pendente','confirmado','nao_confirmado','replica_admin','resolvido') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `movimentacoes`
--

INSERT INTO `movimentacoes` (`id`, `item_id`, `local_origem_id`, `local_destino_id`, `usuario_id`, `data_movimentacao`, `usuario_anterior_id`, `status_confirmacao`) VALUES
(1, 2, 1, 2, 2, '2025-08-04 20:01:17', NULL, 'pendente'),
(2, 2, 2, 1, 2, '2025-08-04 20:05:28', NULL, 'pendente'),
(3, 2, 1, 2, 2, '2025-08-05 11:34:56', NULL, 'pendente'),
(4, 2, 2, 3, 2, '2025-08-05 18:07:49', NULL, 'pendente'),
(6, 5, 1, 1, 2, '2025-08-05 18:28:12', NULL, 'pendente'),
(11, 4, 4, 1, 2, '2025-08-08 20:27:22', 4, 'pendente'),
(12, 11, 4, 1, 2, '2025-08-08 20:27:22', 7, 'pendente'),
(13, 13, 4, 5, 8, '2025-08-13 10:51:47', 8, 'pendente'),
(14, 33, 24, 2, 2, '2025-08-13 14:09:38', 8, 'pendente'),
(15, 32, 24, 2, 2, '2025-08-13 14:09:38', 8, 'pendente'),
(16, 31, 24, 2, 2, '2025-08-13 14:09:38', 8, 'pendente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `administrador_id` int NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `mensagem` text NOT NULL,
  `itens_ids` text,
  `status` enum('Pendente','Confirmado','Nao Confirmado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
  `data_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `justificativa` text,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `admin_reply` text,
  `admin_reply_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `administrador_id`, `tipo`, `mensagem`, `itens_ids`, `status`, `data_envio`, `justificativa`, `data_resposta`, `admin_reply`, `admin_reply_date`) VALUES
(1, 2, 2, 'transferencia', 'Uma movimentação de inventário foi registrada para os seguintes itens: Frigobar consul, Monitor. Eles foram atribuídos a você. Por favor, confirme o recebimento.', '4,11', 'Pendente', '2025-08-08 20:27:22', 'dsfds', '2025-08-08 20:28:05', 'por favor aceite esse item', '2025-08-11 11:30:38'),
(2, 8, 8, 'atribuicao', 'Você recebeu um novo item: COMPUTADOR HP ELITEDEK (Patrimônio: 99105). Por favor, confirme o recebimento.', '13', 'Pendente', '2025-08-12 19:37:45', NULL, NULL, NULL, NULL),
(3, 8, 8, 'atribuicao', 'Você recebeu um novo item: MONITOR HP V206HZ (Patrimônio: 71815). Por favor, confirme o recebimento.', '14', 'Pendente', '2025-08-12 19:38:46', NULL, NULL, NULL, NULL),
(4, 8, 8, 'atribuicao', 'Você recebeu um novo item: MONITOR POSITIVO (Patrimônio: 108505). Por favor, confirme o recebimento.', '15', 'Pendente', '2025-08-12 19:39:38', NULL, NULL, NULL, NULL),
(5, 8, 8, 'transferencia', 'Uma movimentação de inventário foi registrada para os seguintes itens: COMPUTADOR HP ELITEDEK. Eles foram atribuídos a você. Por favor, confirme o recebimento.', '13', 'Pendente', '2025-08-13 10:51:47', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes_itens_detalhes`
--

CREATE TABLE `notificacoes_itens_detalhes` (
  `id` int NOT NULL,
  `notificacao_id` int NOT NULL,
  `item_id` int NOT NULL,
  `status_item` enum('Pendente','Confirmado','Nao Confirmado','Em Disputa','Movimento Desfeito') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pendente',
  `justificativa_usuario` text COLLATE utf8mb4_general_ci,
  `data_justificativa` timestamp NULL DEFAULT NULL,
  `admin_reply` text COLLATE utf8mb4_general_ci,
  `data_admin_reply` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes_movimentacao`
--

CREATE TABLE `notificacoes_movimentacao` (
  `id` int NOT NULL,
  `movimentacao_id` int NOT NULL,
  `item_id` int NOT NULL,
  `usuario_notificado_id` int NOT NULL,
  `status_confirmacao` enum('Pendente','Confirmado','Rejeitado','Replicado','Em Disputa','Movimento Desfeito') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pendente',
  `justificativa_usuario` text COLLATE utf8mb4_general_ci,
  `resposta_admin` text COLLATE utf8mb4_general_ci,
  `data_notificacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes_movimentacao`
--

INSERT INTO `notificacoes_movimentacao` (`id`, `movimentacao_id`, `item_id`, `usuario_notificado_id`, `status_confirmacao`, `justificativa_usuario`, `resposta_admin`, `data_notificacao`, `data_atualizacao`) VALUES
(1, 14, 33, 8, 'Pendente', NULL, NULL, '2025-08-13 14:09:38', '2025-08-13 14:09:38'),
(2, 15, 32, 8, 'Pendente', NULL, NULL, '2025-08-13 14:09:38', '2025-08-13 14:09:38'),
(3, 16, 31, 8, 'Pendente', NULL, NULL, '2025-08-13 14:09:38', '2025-08-13 14:09:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes_respostas_historico`
--

CREATE TABLE `notificacoes_respostas_historico` (
  `id` int NOT NULL,
  `notificacao_movimentacao_id` int NOT NULL,
  `remetente_id` int NOT NULL,
  `tipo_remetente` varchar(10) NOT NULL,
  `conteudo_resposta` text NOT NULL,
  `data_resposta` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int NOT NULL,
  `nome` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `perfis`
--

INSERT INTO `perfis` (`id`, `nome`) VALUES
(1, 'Administrador'),
(2, 'Gestor'),
(3, 'Visualizador');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pendente','aprovado','rejeitado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendente',
  `permissao_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `status`, `permissao_id`) VALUES
(1, 'visualizador', 'visualizador@example.com', '$2y$10$a7fkZK0451826XyCWQs1TuPslZLRCTApNsOoz0LBFqHZsZAjN7SRS', 'aprovado', 3),
(2, 'Marcelo Bezerra de Vasconcelos', 'marce7o77@gmail.com', '$2y$10$cqQbICTdOfveK6vKNKIGKehdqiXpNXJfXegTYYQVsc.1vWZvriWOy', 'aprovado', 1),
(3, 'Renato Soares Laurentino', 'renatosoares@gmail.com', '$2y$10$Z25yQNFJ1uAH/iNfhqpjTeWk0szy4FBJyK7yRV1qDGhVn2SLD/gwm', 'aprovado', 2),
(4, 'teste', 'teste@gmail.com', '$2y$10$vmTmBLaAd/pSvcVsBWk8/OhwHkbuI/QYnT/kEEDlPEQv.vRzMMByK', 'aprovado', 3),
(5, 'Jorge Vieira Rodrigues', 'jorge.vieira@ufrpe.br', '$2y$12$5IM3tfnulayyB58bL9WkbutPgk0vvRRpoU1KKbRs3VOXZUQDOiVw.', 'aprovado', 1),
(7, 'Maria Livânia Dantas de Vasconcelos', 'maria.dvasconcelos@ufrpe.br', '$2y$12$kRhfawI/J82Bt1pgF4t1AuMmWsOI/2QA.Y7eV7BFXu4rocldelDqa', 'aprovado', 2),
(8, 'Vonaldo Feitosa de Siqueira', 'vonaldo.siqueira@ufrpe.br', '$2y$12$1YDiR884C7ooSVcNXTwTwO5GQAAhUgTn2QZqzHKFpoZcAWs7A/Xse', 'aprovado', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `itens`
--
ALTER TABLE `itens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patrimonio_novo` (`patrimonio_novo`),
  ADD KEY `local_id` (`local_id`),
  ADD KEY `responsavel_id` (`responsavel_id`),
  ADD KEY `itens_ibfk_3` (`usuario_anterior_id`);

--
-- Índices de tabela `locais`
--
ALTER TABLE `locais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_solicitado_por` (`solicitado_por`);

--
-- Índices de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `local_origem_id` (`local_origem_id`),
  ADD KEY `local_destino_id` (`local_destino_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_movimentacoes_usuario_anterior` (`usuario_anterior_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `administrador_id` (`administrador_id`);

--
-- Índices de tabela `notificacoes_itens_detalhes`
--
ALTER TABLE `notificacoes_itens_detalhes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notificacao_item_unique` (`notificacao_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Índices de tabela `notificacoes_movimentacao`
--
ALTER TABLE `notificacoes_movimentacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimentacao_id` (`movimentacao_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `usuario_notificado_id` (`usuario_notificado_id`);

--
-- Índices de tabela `notificacoes_respostas_historico`
--
ALTER TABLE `notificacoes_respostas_historico`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_permissao` (`permissao_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `itens`
--
ALTER TABLE `itens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `notificacoes_itens_detalhes`
--
ALTER TABLE `notificacoes_itens_detalhes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacoes_movimentacao`
--
ALTER TABLE `notificacoes_movimentacao`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `notificacoes_respostas_historico`
--
ALTER TABLE `notificacoes_respostas_historico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `itens`
--
ALTER TABLE `itens`
  ADD CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `itens_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `itens_ibfk_3` FOREIGN KEY (`usuario_anterior_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `locais`
--
ALTER TABLE `locais`
  ADD CONSTRAINT `fk_solicitado_por` FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `fk_movimentacoes_usuario_anterior` FOREIGN KEY (`usuario_anterior_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`local_origem_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_3` FOREIGN KEY (`local_destino_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`administrador_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `perfis` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

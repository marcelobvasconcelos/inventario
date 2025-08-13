-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13/08/2025 às 18:16
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

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
-- Estrutura para tabela `itens`
--

CREATE TABLE `itens` (
  `id` int(11) NOT NULL,
  `processo_documento` varchar(100) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao_detalhada` varchar(200) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1,
  `patrimonio_novo` varchar(50) NOT NULL,
  `patrimonio_secundario` varchar(50) DEFAULT NULL,
  `local_id` int(11) NOT NULL,
  `responsavel_id` int(11) NOT NULL,
  `estado` enum('Bom','Razoável','Inservível') NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_anterior_id` int(11) DEFAULT NULL,
  `empenho` varchar(100) DEFAULT NULL,
  `data_emissao_empenho` date DEFAULT NULL,
  `fornecedor` varchar(150) DEFAULT NULL,
  `cnpj_cpf_fornecedor` varchar(20) DEFAULT NULL,
  `cnpj_fornecedor` varchar(20) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `valor_nf` decimal(10,2) DEFAULT NULL,
  `nd_nota_despesa` varchar(100) DEFAULT NULL,
  `unidade_medida` varchar(50) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `nota_fiscal_documento` varchar(100) DEFAULT NULL,
  `data_entrada_aceitacao` date DEFAULT NULL,
  `status_confirmacao` enum('Pendente','Confirmado','Nao Confirmado','Movimento Desfeito') DEFAULT 'Pendente',
  `admin_reply` text DEFAULT NULL,
  `admin_reply_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `itens`
--

INSERT INTO `itens` (`id`, `processo_documento`, `nome`, `descricao_detalhada`, `numero_serie`, `quantidade`, `patrimonio_novo`, `patrimonio_secundario`, `local_id`, `responsavel_id`, `estado`, `observacao`, `data_cadastro`, `usuario_anterior_id`, `empenho`, `data_emissao_empenho`, `fornecedor`, `cnpj_cpf_fornecedor`, `cnpj_fornecedor`, `categoria`, `valor_nf`, `nd_nota_despesa`, `unidade_medida`, `valor`, `nota_fiscal_documento`, `data_entrada_aceitacao`, `status_confirmacao`, `admin_reply`, `admin_reply_date`) VALUES
(1, NULL, 'Computador desktop ', NULL, NULL, 1, '12108/2014', '12108/2014', 5, 7, 'Razoável', 'Computador', '2025-08-04 19:47:52', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Confirmado', NULL, NULL),
(2, NULL, 'Monitor', NULL, NULL, 1, '12858/2014', '12858/2014', 5, 7, 'Bom', 'Monitor de uso', '2025-08-04 19:48:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Confirmado', NULL, NULL),
(3, NULL, 'Monitor HP', NULL, NULL, 1, '096302', '096302', 5, 7, 'Bom', 'Monitor utilizado', '2025-08-05 15:59:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Confirmado', NULL, NULL),
(10, NULL, 'item 05', NULL, NULL, 1, '123', '123', 4, 2, 'Bom', '', '2025-08-06 20:13:01', NULL, 'empenho 123', '2025-08-07', 'fulano', NULL, '123213434', '12345', 234.00, '123', 'unidade', 321.00, NULL, NULL, 'Pendente', NULL, NULL),
(11, NULL, 'item 05', NULL, NULL, 1, '12369656.', '321231', 4, 2, 'Bom', '', '2025-08-06 20:14:01', NULL, 'empenho 123', '2025-08-07', 'fulano', NULL, '123213434', '12345', 234.00, '123', 'unidade', 321.00, NULL, NULL, 'Pendente', NULL, NULL),
(12, NULL, 'item_completo', NULL, NULL, 1, '1000', NULL, 3, 7, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(13, NULL, 'item_completo', NULL, NULL, 1, '1001', NULL, 1, 7, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Movimento Desfeito', NULL, NULL),
(14, NULL, 'item_completo', NULL, NULL, 1, '1002', NULL, 1, 7, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Movimento Desfeito', NULL, NULL),
(15, NULL, 'item_completo', NULL, NULL, 1, '1003', NULL, 1, 7, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(16, NULL, 'item_completo', NULL, NULL, 1, '1004', NULL, 23, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(17, NULL, 'item_completo', NULL, NULL, 1, '1005', NULL, 23, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(18, NULL, 'item_completo', NULL, NULL, 1, '1006', NULL, 23, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(19, NULL, 'item_completo', NULL, NULL, 1, '1007', NULL, 23, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(20, NULL, 'item_completo', NULL, NULL, 1, '1008', NULL, 23, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(21, NULL, 'item_completo', NULL, NULL, 1, '1009', NULL, 23, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(22, NULL, 'item_completo', NULL, NULL, 1, '1010', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(23, NULL, 'item_completo', NULL, NULL, 1, '1011', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(24, NULL, 'item_completo', NULL, NULL, 1, '1012', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(25, NULL, 'item_completo', NULL, NULL, 1, '1013', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(26, NULL, 'item_completo', NULL, NULL, 1, '1014', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(27, NULL, 'item_completo', NULL, NULL, 1, '1015', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(28, NULL, 'item_completo', NULL, NULL, 1, '1016', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(29, NULL, 'item_completo', NULL, NULL, 1, '1017', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(30, NULL, 'item_completo', NULL, NULL, 1, '1018', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(31, NULL, 'item_completo', NULL, NULL, 1, '1019', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(32, NULL, 'item_completo', NULL, NULL, 1, '1020', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(33, NULL, 'item_completo', NULL, NULL, 1, '1021', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(34, NULL, 'item_completo', NULL, NULL, 1, '1022', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Nao Confirmado', NULL, NULL),
(35, NULL, 'item_completo', NULL, NULL, 1, '1023', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(36, NULL, 'item_completo', NULL, NULL, 1, '1024', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(37, NULL, 'item_completo', NULL, NULL, 1, '1025', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(38, NULL, 'item_completo', NULL, NULL, 1, '1026', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Pendente', NULL, NULL),
(39, NULL, 'item_completo', NULL, NULL, 1, '1027', NULL, 3, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Confirmado', NULL, NULL),
(40, NULL, 'item_completo', NULL, NULL, 1, '1028', NULL, 3, 2, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, '', NULL, NULL),
(41, NULL, 'item_completo', NULL, NULL, 1, '1029', NULL, 23, 4, 'Bom', 'feito no formulário de itens em LOTE', '2025-08-07 12:59:17', NULL, '8476284376321', '2025-08-07', 'fornecedor teste', NULL, '12323423453523', '122343', 250000.00, '123456', 'UNIDADE', 833.33, NULL, NULL, 'Movimento Desfeito', NULL, NULL),
(42, NULL, 'computador de mesa', NULL, NULL, 1, '123456', '123456', 23, 4, '', 'Computador bom', '2025-08-13 13:35:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pendente', NULL, NULL),
(50, NULL, 'Monitor HP', NULL, NULL, 1, '123455', '1234567', 23, 4, '', 'Do meu computador de mesa', '2025-08-13 13:40:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Confirmado', NULL, NULL);

--
-- Índices para tabelas despejadas
--

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
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `itens`
--
ALTER TABLE `itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

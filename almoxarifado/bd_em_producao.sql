-- =========================================================================
-- SCRIPT DE ATUALIZAÇÃO DO BANCO DE DADOS
-- =========================================================================
--
-- ATENÇÃO:
-- 1. FAÇA UM BACKUP COMPLETO DO SEU BANCO DE DADOS ANTES DE EXECUTAR ESTE SCRIPT.
-- 2. Este script foi criado para ser o mais seguro possível, utilizando ALTER TABLE
--    e CREATE TABLE IF NOT EXISTS, mas a estrutura do seu banco de produção
--    pode ser diferente do esperado.
-- 3. Revise cada comando e ajuste conforme necessário para sua base de dados.
--
-- =========================================================================

-- Desativa a verificação de chaves estrangeiras para evitar erros de dependência
-- durante as alterações. Elas serão reativadas ao final.
SET foreign_key_checks = 0;

-- --------------------------------------------------------
-- 1. COMANDOS PARA TABELAS QUE JÁ EXISTEM (ALTER TABLE)
-- --------------------------------------------------------

-- Tabela `itens`
-- Adiciona colunas que podem ser novas na tabela de itens
ALTER TABLE `itens`
ADD COLUMN `cnpj_fornecedor` varchar(20) DEFAULT NULL,
ADD COLUMN `categoria` varchar(100) DEFAULT NULL,
ADD COLUMN `valor_nf` decimal(10,2) DEFAULT NULL,
ADD COLUMN `nd_nota_despesa` varchar(100) DEFAULT NULL,
ADD COLUMN `unidade_medida` varchar(50) DEFAULT NULL,
ADD COLUMN `valor` decimal(10,2) DEFAULT NULL,
ADD COLUMN `tipo_aquisicao` enum('compra','outra') DEFAULT 'compra',
ADD COLUMN `tipo_aquisicao_descricao` varchar(100) DEFAULT NULL,
ADD COLUMN `numero_documento` varchar(100) DEFAULT NULL,
ADD COLUMN `nota_fiscal_documento` varchar(100) DEFAULT NULL,
ADD COLUMN `data_entrada_aceitacao` date DEFAULT NULL,
ADD COLUMN `status_confirmacao` enum('Pendente','Confirmado','Nao Confirmado','Movimento Desfeito') DEFAULT 'Pendente',
ADD COLUMN `admin_reply` text DEFAULT NULL,
ADD COLUMN `admin_reply_date` timestamp NULL DEFAULT NULL;

-- Tabela `movimentacoes`
-- Adiciona colunas que podem ser novas na tabela de movimentações
ALTER TABLE `movimentacoes`
ADD COLUMN `usuario_anterior_id` int(11) DEFAULT NULL,
ADD COLUMN `status_confirmacao` enum('pendente','confirmado','nao_confirmado','replica_admin','resolvido') NOT NULL DEFAULT 'pendente',
ADD COLUMN `justificativa_usuario` text DEFAULT NULL,
ADD COLUMN `replica_admin` text DEFAULT NULL,
ADD COLUMN `data_acao_usuario` datetime DEFAULT NULL,
ADD COLUMN `data_replica_admin` datetime DEFAULT NULL,
ADD COLUMN `usuario_destino_id` int(11) DEFAULT NULL;

-- Tabela `notificacoes_itens_detalhes`
-- Adiciona colunas que podem ser novas
ALTER TABLE `notificacoes_itens_detalhes`
ADD COLUMN `justificativa_usuario` text DEFAULT NULL,
ADD COLUMN `data_justificativa` timestamp NULL DEFAULT NULL,
ADD COLUMN `admin_reply` text DEFAULT NULL,
ADD COLUMN `data_admin_reply` timestamp NULL DEFAULT NULL;


-- --------------------------------------------------------
-- 2. COMANDOS PARA NOVAS TABELAS (CREATE TABLE IF NOT EXISTS)
-- --------------------------------------------------------

-- Tabela `almoxarifado_agendamentos`
CREATE TABLE IF NOT EXISTS `almoxarifado_agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisicao_id` int(11) NOT NULL,
  `data_agendamento` datetime NOT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('agendado','concluido','cancelado') NOT NULL DEFAULT 'agendado',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requisicao_id` (`requisicao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `almoxarifado_entradas`
CREATE TABLE IF NOT EXISTS `almoxarifado_entradas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `fornecedor` varchar(150) DEFAULT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `data_entrada` date NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `almoxarifado_materiais`
CREATE TABLE IF NOT EXISTS `almoxarifado_materiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `unidade_medida` varchar(20) NOT NULL,
  `estoque_minimo` decimal(10,2) DEFAULT 0.00,
  `estoque_atual` decimal(10,2) DEFAULT 0.00,
  `valor_unitario` decimal(10,2) DEFAULT 0.00,
  `categoria` varchar(100) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `local_id` int(11) DEFAULT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'Novo',
  `status_confirmacao` varchar(50) NOT NULL DEFAULT 'Pendente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_almoxarifado_local` (`local_id`),
  KEY `fk_almoxarifado_responsavel` (`responsavel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `almoxarifado_movimentacoes`
CREATE TABLE IF NOT EXISTS `almoxarifado_movimentacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_atual` decimal(10,2) NOT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `almoxarifado_produtos`
CREATE TABLE IF NOT EXISTS `almoxarifado_produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `unidade_medida` varchar(50) DEFAULT NULL,
  `estoque_atual` int(11) DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `almoxarifado_requisicoes`
CREATE TABLE IF NOT EXISTS `almoxarifado_requisicoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `data_requisicao` datetime NOT NULL,
  `status` enum('pendente','aprovada','rejeitada','concluida') DEFAULT 'pendente',
  `justificativa` text DEFAULT NULL,
  `status_notificacao` enum('pendente','em_discussao','aprovada','rejeitada','agendada','concluida') NOT NULL DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `local_id` (`local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `almoxarifado_requisicoes_conversas`
CREATE TABLE IF NOT EXISTS `almoxarifado_requisicoes_conversas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo_usuario` enum('requisitante','administrador') NOT NULL,
  `data_mensagem` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notificacao_id` (`notificacao_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `almoxarifado_requisicoes_itens`
CREATE TABLE IF NOT EXISTS `almoxarifado_requisicoes_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisicao_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_solicitada` int(11) NOT NULL,
  `quantidade_entregue` int(11) DEFAULT 0,
  `observacao` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requisicao_id` (`requisicao_id`),
  KEY `produto_id` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `almoxarifado_requisicoes_notificacoes`
CREATE TABLE IF NOT EXISTS `almoxarifado_requisicoes_notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisicao_id` int(11) NOT NULL,
  `usuario_origem_id` int(11) NOT NULL,
  `usuario_destino_id` int(11) NOT NULL,
  `tipo` enum('nova_requisicao','resposta_admin','resposta_usuario','aprovada','rejeitada','agendamento') NOT NULL,
  `mensagem` text NOT NULL,
  `status` enum('pendente','lida','respondida','concluida') NOT NULL DEFAULT 'pendente',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_leitura` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requisicao_id` (`requisicao_id`),
  KEY `usuario_origem_id` (`usuario_origem_id`),
  KEY `usuario_destino_id` (`usuario_destino_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `almoxarifado_saidas`
CREATE TABLE IF NOT EXISTS `almoxarifado_saidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `setor_destino` varchar(100) DEFAULT NULL,
  `responsavel_saida` varchar(150) DEFAULT NULL,
  `data_saida` date NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `categorias`
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela `configuracoes`
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(255) NOT NULL,
  `valor` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `empenhos`
CREATE TABLE IF NOT EXISTS `empenhos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_empenho` varchar(50) NOT NULL,
  `data_emissao` date NOT NULL,
  `nome_fornecedor` varchar(255) NOT NULL,
  `cnpj_fornecedor` varchar(18) NOT NULL,
  `status` enum('Aberto','Fechado') NOT NULL DEFAULT 'Aberto',
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `categoria_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_empenho` (`numero_empenho`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela `empenhos_insumos`
CREATE TABLE IF NOT EXISTS `empenhos_insumos` (
  `numero` varchar(50) NOT NULL,
  `data_emissao` date NOT NULL,
  `fornecedor` varchar(150) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `status` enum('Aberto','Fechado') NOT NULL DEFAULT 'Aberto',
  PRIMARY KEY (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `locais`
CREATE TABLE IF NOT EXISTS `locais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `status` enum('aprovado','pendente','rejeitado') DEFAULT 'aprovado',
  `solicitado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_solicitado_por` (`solicitado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `materiais`
CREATE TABLE IF NOT EXISTS `materiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `qtd` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `valor_unit` decimal(10,2) NOT NULL,
  `nota_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `nota_no` (`nota_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `notas_fiscais`
CREATE TABLE IF NOT EXISTS `notas_fiscais` (
  `nota_numero` varchar(50) NOT NULL,
  `nota_valor` decimal(10,2) NOT NULL,
  `empenho_numero` varchar(50) NOT NULL,
  PRIMARY KEY (`nota_numero`),
  KEY `empenho_numero` (`empenho_numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `notificacoes`
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `administrador_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `mensagem` text NOT NULL,
  `status` enum('Pendente','Confirmado','Nao Confirmado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
  `data_envio` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `administrador_id` (`administrador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci;

-- Tabela `notificacoes_almoxarifado_detalhes`
CREATE TABLE IF NOT EXISTS `notificacoes_almoxarifado_detalhes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `status_item` varchar(50) NOT NULL DEFAULT 'Pendente',
  PRIMARY KEY (`id`),
  KEY `notificacao_id` (`notificacao_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `notificacoes_almoxarifado_respostas`
CREATE TABLE IF NOT EXISTS `notificacoes_almoxarifado_respostas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `justificativa` text NOT NULL,
  `data_resposta` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notificacao_id` (`notificacao_id`),
  KEY `item_id` (`item_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `notificacoes_movimentacao`
CREATE TABLE IF NOT EXISTS `notificacoes_movimentacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movimentacao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `usuario_notificado_id` int(11) NOT NULL,
  `status_confirmacao` enum('Pendente','Confirmado','Rejeitado','Replicado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
  `justificativa_usuario` text DEFAULT NULL,
  `resposta_admin` text DEFAULT NULL,
  `data_notificacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `movimentacao_id` (`movimentacao_id`),
  KEY `item_id` (`item_id`),
  KEY `usuario_notificado_id` (`usuario_notificado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `notificacoes_respostas_historico`
CREATE TABLE IF NOT EXISTS `notificacoes_respostas_historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_movimentacao_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `tipo_remetente` varchar(10) NOT NULL,
  `conteudo_resposta` text NOT NULL,
  `data_resposta` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notificacao_movimentacao_id` (`notificacao_movimentacao_id`),
  KEY `remetente_id` (`remetente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci;

-- Tabela `perfis`
CREATE TABLE IF NOT EXISTS `perfis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela `rascunhos_itens`
CREATE TABLE IF NOT EXISTS `rascunhos_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `local_id` int(11) DEFAULT NULL,
  `empenho_id` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `local_id` (`local_id`),
  KEY `empenho_id` (`empenho_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci;

-- Tabela `solicitacoes_senha`
CREATE TABLE IF NOT EXISTS `solicitacoes_senha` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nome_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pendente','processada','cancelada') DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci;

-- Tabela `usuarios`
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status` enum('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  `permissao_id` int(11) DEFAULT NULL,
  `tema_preferido` varchar(20) DEFAULT 'padrao',
  `senha_temporaria` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_permissao` (`permissao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- 3. ADIÇÃO DE CHAVES ESTRANGEIRAS
-- --------------------------------------------------------
-- (Essas chaves devem ser adicionadas após a criação/atualização das tabelas)

-- Tabela `almoxarifado_agendamentos`
ALTER TABLE `almoxarifado_agendamentos` ADD CONSTRAINT `fk_agendamento_requisicao` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabela `almoxarifado_entradas`
ALTER TABLE `almoxarifado_entradas`
  ADD CONSTRAINT `almoxarifado_entradas_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`),
  ADD CONSTRAINT `almoxarifado_entradas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

-- Tabela `almoxarifado_materiais`
ALTER TABLE `almoxarifado_materiais`
  ADD CONSTRAINT `fk_almoxarifado_local` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_almoxarifado_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Tabela `almoxarifado_movimentacoes`
ALTER TABLE `almoxarifado_movimentacoes`
  ADD CONSTRAINT `almoxarifado_movimentacoes_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`),
  ADD CONSTRAINT `almoxarifado_movimentacoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

-- Tabela `almoxarifado_requisicoes`
ALTER TABLE `almoxarifado_requisicoes`
  ADD CONSTRAINT `almoxarifado_requisicoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `almoxarifado_requisicoes_ibfk_2` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`);

-- Tabela `almoxarifado_requisicoes_conversas`
ALTER TABLE `almoxarifado_requisicoes_conversas`
  ADD CONSTRAINT `fk_conv_notif_req` FOREIGN KEY (`notificacao_id`) REFERENCES `almoxarifado_requisicoes_notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabela `almoxarifado_requisicoes_itens`
ALTER TABLE `almoxarifado_requisicoes_itens`
  ADD CONSTRAINT `almoxarifado_requisicoes_itens_ibfk_1` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`),
  ADD CONSTRAINT `almoxarifado_requisicoes_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `almoxarifado_produtos` (`id`);

-- Tabela `almoxarifado_requisicoes_notificacoes`
ALTER TABLE `almoxarifado_requisicoes_notificacoes`
  ADD CONSTRAINT `fk_notif_req_requisicao` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_req_usuario_destino` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_req_usuario_origem` FOREIGN KEY (`usuario_origem_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabela `almoxarifado_saidas`
ALTER TABLE `almoxarifado_saidas`
  ADD CONSTRAINT `almoxarifado_saidas_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`),
  ADD CONSTRAINT `almoxarifado_saidas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

-- Tabela `itens`
ALTER TABLE `itens`
  ADD CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `itens_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `itens_ibfk_3` FOREIGN KEY (`usuario_anterior_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `itens_ibfk_4` FOREIGN KEY (`empenho_id`) REFERENCES `empenhos` (`id`);

-- Tabela `locais`
ALTER TABLE `locais` ADD CONSTRAINT `fk_solicitado_por` FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios` (`id`);

-- Tabela `materiais`
ALTER TABLE `materiais`
  ADD CONSTRAINT `materiais_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `materiais_ibfk_2` FOREIGN KEY (`nota_no`) REFERENCES `notas_fiscais` (`nota_numero`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Tabela `movimentacoes`
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `fk_movimentacoes_usuario_anterior` FOREIGN KEY (`usuario_anterior_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_usuario_destino` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`local_origem_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_3` FOREIGN KEY (`local_destino_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

-- Tabela `notas_fiscais`
ALTER TABLE `notas_fiscais` ADD CONSTRAINT `notas_fiscais_ibfk_1` FOREIGN KEY (`empenho_numero`) REFERENCES `empenhos_insumos` (`numero`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabela `notificacoes`
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`administrador_id`) REFERENCES `usuarios` (`id`);

-- Tabela `notificacoes_almoxarifado_detalhes`
ALTER TABLE `notificacoes_almoxarifado_detalhes`
  ADD CONSTRAINT `fk_notificacoes_almoxarifado_detalhes_item` FOREIGN KEY (`item_id`) REFERENCES `almoxarifado_materiais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notificacoes_almoxarifado_detalhes_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabela `notificacoes_almoxarifado_respostas`
ALTER TABLE `notificacoes_almoxarifado_respostas`
  ADD CONSTRAINT `fk_respostas_almoxarifado_item` FOREIGN KEY (`item_id`) REFERENCES `almoxarifado_materiais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_respostas_almoxarifado_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_respostas_almoxarifado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabela `notificacoes_itens_detalhes`
ALTER TABLE `notificacoes_itens_detalhes`
  ADD CONSTRAINT `fk_item_id_detalhes` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notificacao_id` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE;

-- Tabela `notificacoes_movimentacao`
ALTER TABLE `notificacoes_movimentacao`
  ADD CONSTRAINT `notificacoes_movimentacao_ibfk_1` FOREIGN KEY (`movimentacao_id`) REFERENCES `movimentacoes` (`id`),
  ADD CONSTRAINT `notificacoes_movimentacao_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`),
  ADD CONSTRAINT `notificacoes_movimentacao_ibfk_3` FOREIGN KEY (`usuario_notificado_id`) REFERENCES `usuarios` (`id`);

-- Tabela `notificacoes_respostas_historico`
ALTER TABLE `notificacoes_respostas_historico`
  ADD CONSTRAINT `notificacoes_respostas_historico_ibfk_1` FOREIGN KEY (`notificacao_movimentacao_id`) REFERENCES `notificacoes_movimentacao` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificacoes_respostas_historico_ibfk_2` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

-- Tabela `rascunhos_itens`
ALTER TABLE `rascunhos_itens`
  ADD CONSTRAINT `rascunhos_itens_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `rascunhos_itens_ibfk_2` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `rascunhos_itens_ibfk_3` FOREIGN KEY (`empenho_id`) REFERENCES `empenhos` (`id`),
  ADD CONSTRAINT `rascunhos_itens_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

-- Tabela `solicitacoes_senha`
ALTER TABLE `solicitacoes_senha` ADD CONSTRAINT `solicitacoes_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

-- Tabela `usuarios`
ALTER TABLE `usuarios` ADD CONSTRAINT `fk_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `perfis` (`id`);

-- Reativa a verificação de chaves estrangeiras
SET foreign_key_checks = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

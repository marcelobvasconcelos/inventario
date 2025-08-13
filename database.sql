-- Estrutura para tabela `configuracoes`
CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(255) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci;

---
-- Estrutura para tabela `itens`
CREATE TABLE `itens` (
  `id` int(11) NOT NULL,
  `processo_documento` varchar(100) DEFAULT NULL,
  `fornecedor` varchar(150) DEFAULT NULL,
  `cnpj_cpf_fornecedor` varchar(20) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao_detalhada` varchar(200) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1,
  `valor` decimal(10,2) DEFAULT NULL,
  `nota_fiscal_documento` varchar(100) DEFAULT NULL,
  `data_entrada_aceitacao` date DEFAULT NULL,
  `patrimonio_novo` varchar(50) NOT NULL,
  `estado` enum('Em uso','Ocioso','Recuperável','Inservível') NOT NULL,
  `local_id` int(11) NOT NULL,
  `responsavel_id` int(11) NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_anterior_id` int(11) DEFAULT NULL,
  `status_confirmacao` enum('Pendente','Confirmado','Nao Confirmado','Movimento Desfeito') DEFAULT 'Pendente',
  `admin_reply` text DEFAULT NULL,
  `admin_reply_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

---
-- Estrutura para tabela `locais`
CREATE TABLE `locais` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `status` enum('aprovado','pendente','rejeitado') DEFAULT 'aprovado',
  `solicitado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

---
-- Estrutura para tabela `movimentacoes`
CREATE TABLE `movimentacoes` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `local_origem_id` int(11) NOT NULL,
  `local_destino_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_anterior_id` int(11) DEFAULT NULL,
  `status_confirmacao` enum('pendente','confirmado','nao_confirmado','replica_admin','resolvido') NOT NULL DEFAULT 'pendente',
  `justificativa_usuario` text DEFAULT NULL,
  `replica_admin` text DEFAULT NULL,
  `data_acao_usuario` datetime DEFAULT NULL,
  `data_replica_admin` datetime DEFAULT NULL,
  `usuario_destino_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

---
-- Estrutura para tabela `notificacoes`
CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `administrador_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `mensagem` text NOT NULL,
  `status` enum('Pendente','Confirmado','Nao Confirmado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
  `data_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci;

---
-- Estrutura para tabela `notificacoes_itens_detalhes`
CREATE TABLE `notificacoes_itens_detalhes` (
  `id` int(11) NOT NULL,
  `notificacao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `status_item` enum('Pendente','Confirmado','Nao Confirmado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
  `justificativa_usuario` text DEFAULT NULL,
  `data_justificativa` timestamp NULL DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `data_admin_reply` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estrutura para tabela `notificacoes_respostas_historico`

CREATE TABLE notificacoes_respostas_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacao_movimentacao_id INT NOT NULL,
    remetente_id INT NOT NULL,
    tipo_remetente VARCHAR(10) NOT NULL, -- 'usuario' ou 'admin'
    conteudo_resposta TEXT NOT NULL,
    data_resposta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notificacao_movimentacao_id) REFERENCES notificacoes_movimentacao(id) ON DELETE CASCADE,
    FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

---
-- Estrutura para tabela `notificacoes_movimentacao`
CREATE TABLE `notificacoes_movimentacao` (
  `id` int(11) NOT NULL,
  `movimentacao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `usuario_notificado_id` int(11) NOT NULL,
  `status_confirmacao` enum('Pendente','Confirmado','Rejeitado','Replicado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
  `justificativa_usuario` text DEFAULT NULL,
  `resposta_admin` text DEFAULT NULL,
  `data_notificacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

---
-- Estrutura para tabela `perfis`
CREATE TABLE `perfis` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

---
-- Estrutura para tabela `usuarios`
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status` enum('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  `permissao_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

---
-- Índices para tabelas
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

ALTER TABLE `itens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patrimonio_novo` (`patrimonio_novo`),
  ADD KEY `local_id` (`local_id`),
  ADD KEY `responsavel_id` (`responsavel_id`),
  ADD KEY `itens_ibfk_3` (`usuario_anterior_id`);

ALTER TABLE `locais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_solicitado_por` (`solicitado_por`);

ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `local_origem_id` (`local_origem_id`),
  ADD KEY `local_destino_id` (`local_destino_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_movimentacoes_usuario_anterior` (`usuario_anterior_id`),
  ADD KEY `fk_usuario_destino` (`usuario_destino_id`);

ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `administrador_id` (`administrador_id`);

ALTER TABLE `notificacoes_itens_detalhes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notificacao_item_unique` (`notificacao_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

ALTER TABLE `notificacoes_movimentacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimentacao_id` (`movimentacao_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `usuario_notificado_id` (`usuario_notificado_id`);

ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_permissao` (`permissao_id`);

---
-- AUTO_INCREMENT para tabelas
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `locais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notificacoes_itens_detalhes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notificacoes_movimentacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `perfis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

---
-- Restrições para tabelas
ALTER TABLE `itens`
  ADD CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `itens_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `itens_ibfk_3` FOREIGN KEY (`usuario_anterior_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `locais`
  ADD CONSTRAINT `fk_solicitado_por` FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios` (`id`);

ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `fk_movimentacoes_usuario_anterior` FOREIGN KEY (`usuario_anterior_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_usuario_destino` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`local_origem_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_3` FOREIGN KEY (`local_destino_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `movimentacoes_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`administrador_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `notificacoes_itens_detalhes`
  ADD CONSTRAINT `fk_item_id_detalhes` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notificacao_id` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE;

ALTER TABLE `notificacoes_movimentacao`
  ADD CONSTRAINT `notificacoes_movimentacao_ibfk_1` FOREIGN KEY (`movimentacao_id`) REFERENCES `movimentacoes` (`id`),
  ADD CONSTRAINT `notificacoes_movimentacao_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`),
  ADD CONSTRAINT `notificacoes_movimentacao_ibfk_3` FOREIGN KEY (`usuario_notificado_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `perfis` (`id`);

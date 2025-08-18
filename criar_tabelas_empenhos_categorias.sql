-- Criação da tabela de categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Criação da tabela de empenhos
CREATE TABLE IF NOT EXISTS `empenhos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_empenho` varchar(50) NOT NULL,
  `data_emissao` date NOT NULL,
  `nome_fornecedor` varchar(150) NOT NULL,
  `cnpj_fornecedor` varchar(20) NOT NULL,
  `status` enum('Aberto','Fechado') NOT NULL DEFAULT 'Aberto',
  `categoria_id` int(11) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_empenho` (`numero_empenho`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `empenhos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar coluna 'empenho_id' na tabela 'itens'
ALTER TABLE `itens` ADD COLUMN IF NOT EXISTS `empenho_id` int(11) DEFAULT NULL AFTER `cnpj_cpf_fornecedor`,
  ADD CONSTRAINT IF NOT EXISTS `itens_ibfk_4` FOREIGN KEY (`empenho_id`) REFERENCES `empenhos` (`id`);
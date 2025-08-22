-- Script para atualizar a relação entre Empenhos e Categorias para N:N
-- Esta alteração permite que um mesmo empenho seja associado a múltiplas categorias

-- 1. Criar a tabela de junção para a relação N:N entre empenhos e categorias
CREATE TABLE IF NOT EXISTS `empenho_categoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empenho_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_empenho_categoria` (`empenho_id`, `categoria_id`),
  CONSTRAINT `fk_empenho_categoria_empenho` FOREIGN KEY (`empenho_id`) REFERENCES `empenhos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_empenho_categoria_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Migrar os dados existentes da coluna categoria_id na tabela empenhos para a nova tabela de junção
-- Esta query insere registros na tabela de junção para manter os relacionamentos existentes
INSERT IGNORE INTO `empenho_categoria` (`empenho_id`, `categoria_id`)
SELECT `id`, `categoria_id` FROM `empenhos` WHERE `categoria_id` IS NOT NULL;

-- 3. Remover a coluna categoria_id da tabela empenhos, pois não será mais usada
-- Primeiro, remover a chave estrangeira
ALTER TABLE `empenhos` DROP FOREIGN KEY IF EXISTS `empenhos_ibfk_1`;
-- Depois, remover a coluna
ALTER TABLE `empenhos` DROP COLUMN IF EXISTS `categoria_id`;
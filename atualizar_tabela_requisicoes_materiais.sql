-- Script para atualizar a tabela de requisições para usar a nova tabela de materiais

-- Primeiro, remover a chave estrangeira existente
ALTER TABLE `almoxarifado_requisicoes_itens` 
DROP FOREIGN KEY `almoxarifado_requisicoes_itens_ibfk_2`;

-- Em seguida, renomear a coluna produto_id para material_id
ALTER TABLE `almoxarifado_requisicoes_itens` 
CHANGE `produto_id` `material_id` INT(11) NOT NULL;

-- Finalmente, adicionar a nova chave estrangeira referenciando a tabela materiais
ALTER TABLE `almoxarifado_requisicoes_itens` 
ADD CONSTRAINT `almoxarifado_requisicoes_itens_ibfk_2` 
FOREIGN KEY (`material_id`) REFERENCES `materiais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
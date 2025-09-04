<?php
require_once 'config/db.php';

try {
    // Verificar se a tabela notificacoes existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'notificacoes'");
    $tabela_notificacoes = $stmt->rowCount() > 0;
    
    // Verificar se a tabela notificacoes_almoxarifado_detalhes existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'notificacoes_almoxarifado_detalhes'");
    $tabela_detalhes = $stmt->rowCount() > 0;
    
    // Verificar se a tabela notificacoes_almoxarifado_respostas existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'notificacoes_almoxarifado_respostas'");
    $tabela_respostas = $stmt->rowCount() > 0;
    
    echo "Verificação de tabelas:
";
    echo "Tabela 'notificacoes': " . ($tabela_notificacoes ? "EXISTS" : "NOT EXISTS") . "
";
    echo "Tabela 'notificacoes_almoxarifado_detalhes': " . ($tabela_detalhes ? "EXISTS" : "NOT EXISTS") . "
";
    echo "Tabela 'notificacoes_almoxarifado_respostas': " . ($tabela_respostas ? "EXISTS" : "NOT EXISTS") . "
";
    
    // Criar tabelas se não existirem
    if (!$tabela_notificacoes) {
        echo "Criando tabela 'notificacoes'...
";
        $sql = "CREATE TABLE `notificacoes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `usuario_id` int(11) NOT NULL,
          `administrador_id` int(11) NOT NULL,
          `tipo` varchar(50) NOT NULL,
          `mensagem` text NOT NULL,
          `status` enum('Pendente','Confirmado','Nao Confirmado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
          `data_envio` datetime DEFAULT CURRENT_TIMESTAMP(),
          PRIMARY KEY (`id`),
          KEY `usuario_id` (`usuario_id`),
          KEY `administrador_id` (`administrador_id`),
          CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
          CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`administrador_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci";
        
        $pdo->exec($sql);
        echo "Tabela 'notificacoes' criada com sucesso.
";
    }
    
    if (!$tabela_detalhes) {
        echo "Criando tabela 'notificacoes_almoxarifado_detalhes'...
";
        $sql = "CREATE TABLE `notificacoes_almoxarifado_detalhes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `notificacao_id` int(11) NOT NULL,
          `item_id` int(11) NOT NULL,
          `status_item` varchar(50) NOT NULL DEFAULT 'Pendente',
          PRIMARY KEY (`id`),
          KEY `notificacao_id` (`notificacao_id`),
          KEY `item_id` (`item_id`),
          CONSTRAINT `fk_notificacoes_almoxarifado_detalhes_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_notificacoes_almoxarifado_detalhes_item` FOREIGN KEY (`item_id`) REFERENCES `almoxarifado_materiais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "Tabela 'notificacoes_almoxarifado_detalhes' criada com sucesso.
";
    }
    
    if (!$tabela_respostas) {
        echo "Criando tabela 'notificacoes_almoxarifado_respostas'...
";
        $sql = "CREATE TABLE `notificacoes_almoxarifado_respostas` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `notificacao_id` int(11) NOT NULL,
          `item_id` int(11) NOT NULL,
          `usuario_id` int(11) NOT NULL,
          `justificativa` text NOT NULL,
          `data_resposta` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
          PRIMARY KEY (`id`),
          KEY `notificacao_id` (`notificacao_id`),
          KEY `item_id` (`item_id`),
          KEY `usuario_id` (`usuario_id`),
          CONSTRAINT `fk_respostas_almoxarifado_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_respostas_almoxarifado_item` FOREIGN KEY (`item_id`) REFERENCES `almoxarifado_materiais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_respostas_almoxarifado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "Tabela 'notificacoes_almoxarifado_respostas' criada com sucesso.
";
    }
    
    echo "Verificação e criação de tabelas concluída.
";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "
";
}
?>
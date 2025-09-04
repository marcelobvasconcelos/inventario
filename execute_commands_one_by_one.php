<?php
// execute_commands_one_by_one.php - Executar comandos SQL individualmente
require_once 'config/db.php';

$commands = [
    "CREATE TABLE `almoxarifado_requisicoes_notificacoes` (
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
      KEY `usuario_destino_id` (`usuario_destino_id`),
      CONSTRAINT `fk_notif_req_requisicao` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT `fk_notif_req_usuario_origem` FOREIGN KEY (`usuario_origem_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT `fk_notif_req_usuario_destino` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE `almoxarifado_requisicoes_conversas` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `notificacao_id` int(11) NOT NULL,
      `usuario_id` int(11) NOT NULL,
      `mensagem` text NOT NULL,
      `tipo_usuario` enum('requisitante','administrador') NOT NULL,
      `data_mensagem` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `notificacao_id` (`notificacao_id`),
      KEY `usuario_id` (`usuario_id`),
      CONSTRAINT `fk_conv_notif_req` FOREIGN KEY (`notificacao_id`) REFERENCES `almoxarifado_requisicoes_notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT `fk_conv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE `almoxarifado_agendamentos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `requisicao_id` int(11) NOT NULL,
      `data_agendamento` datetime NOT NULL,
      `observacoes` text,
      `status` enum('agendado','concluido','cancelado') NOT NULL DEFAULT 'agendado',
      `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
      `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `requisicao_id` (`requisicao_id`),
      CONSTRAINT `fk_agendamento_requisicao` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "ALTER TABLE `almoxarifado_requisicoes` 
    ADD COLUMN `status_notificacao` enum('pendente','em_discussao','aprovada','rejeitada','agendada','concluida') NOT NULL DEFAULT 'pendente'"
];

echo "<h2>Executando comandos SQL individualmente...</h2>";

foreach ($commands as $index => $command) {
    echo "<h3>Comando " . ($index + 1) . ":</h3>";
    echo "<pre>" . htmlspecialchars($command) . "</pre>";
    
    if (mysqli_query($link, $command)) {
        echo "<p style='color: green;'>✓ Sucesso!</p>";
    } else {
        echo "<p style='color: red;'>✗ Erro: " . mysqli_error($link) . "</p>";
    }
    echo "<hr>";
}

mysqli_close($link);
?>
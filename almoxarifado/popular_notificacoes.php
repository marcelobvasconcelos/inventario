<?php
require_once '../config/db.php';

echo "<pre>"; // Para formatar a saída

try {
    $pdo->beginTransaction();

    echo "Iniciando povoamento de notificações e conversas...\n\n";

    // IDs de exemplo (ajuste conforme seu banco de dados)
    $requisicao_exemplo_id = 8; // ID de uma requisição existente
    $usuario_requisitante_id = 2; // ID de um usuário comum
    $usuario_admin_id = 2;      // ID de um usuário administrador

    // Verificar se a requisição e os usuários existem
    $stmt_check_req = $pdo->prepare("SELECT id FROM almoxarifado_requisicoes WHERE id = ?");
    $stmt_check_req->execute([$requisicao_exemplo_id]);
    if (!$stmt_check_req->fetch()) {
        die("Erro: Requisição ID {$requisicao_exemplo_id} não encontrada. Por favor, crie uma requisição primeiro.\n");
    }

    $stmt_check_user = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt_check_user->execute([$usuario_requisitante_id]);
    if (!$stmt_check_user->fetch()) {
        die("Erro: Usuário requisitante ID {$usuario_requisitante_id} não encontrado.\n");
    }

    $stmt_check_user->execute([$usuario_admin_id]);
    if (!$stmt_check_user->fetch()) {
        die("Erro: Usuário administrador ID {$usuario_admin_id} não encontrado.\n");
    }

    // --- 1. Criar Notificação Inicial (se não existir para esta requisição e usuários)
    echo "--- Criando Notificação Inicial ---\n";
    $sql_check_notif = "SELECT id FROM almoxarifado_requisicoes_notificacoes WHERE requisicao_id = ? AND usuario_origem_id = ? AND usuario_destino_id = ? AND tipo = 'nova_requisicao'";
    $stmt_check_notif = $pdo->prepare($sql_check_notif);
    $stmt_check_notif->execute([$requisicao_exemplo_id, $usuario_requisitante_id, $usuario_admin_id]);
    $notificacao_id = $stmt_check_notif->fetchColumn();

    if (!$notificacao_id) {
        $mensagem_notificacao_inicial = "Nova requisição de materiais #{$requisicao_exemplo_id} criada por Usuário Teste.";
        $sql_insert_notif = "INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'nova_requisicao', ?, 'pendente')";
        $stmt_insert_notif = $pdo->prepare($sql_insert_notif);
        $stmt_insert_notif->execute([$requisicao_exemplo_id, $usuario_requisitante_id, $usuario_admin_id, $mensagem_notificacao_inicial]);
        $notificacao_id = $pdo->lastInsertId();
        echo "Notificação inicial para Requisição #{$requisicao_exemplo_id} criada com ID {$notificacao_id}.\n";
    } else {
        echo "Notificação inicial para Requisição #{$requisicao_exemplo_id} já existe (ID {$notificacao_id}).\n";
    }
    echo "Notificação inicial processada com sucesso!\n\n";

    // --- 2. Popular Conversa (até 5 perguntas e respostas)
    echo "--- Populando Conversa ---\n";
    $mensagens_conversa = [
        ['autor' => 'requisitante', 'mensagem' => 'Olá, gostaria de saber o status da minha requisição.'],
        ['autor' => 'admin', 'mensagem' => 'Olá! Sua requisição está em análise. Precisamos de mais detalhes sobre o uso dos itens.'],
        ['autor' => 'requisitante', 'mensagem' => 'Certo. Os itens são para o projeto X, fase de prototipagem. Precisamos deles com urgência.'],
        ['autor' => 'admin', 'mensagem' => 'Entendido. Vou verificar a disponibilidade e te retorno em breve.'],
        ['autor' => 'requisitante', 'mensagem' => 'Agradeço o retorno. Fico no aguardo.']
    ];

    foreach ($mensagens_conversa as $index => $msg) {
        $tipo_usuario = ($msg['autor'] == 'requisitante') ? 'requisitante' : 'administrador';
        $usuario_id = ($msg['autor'] == 'requisitante') ? $usuario_requisitante_id : $usuario_admin_id;

        // Verificar se a mensagem já existe para evitar duplicatas
        $sql_check_msg = "SELECT COUNT(*) FROM almoxarifado_requisicoes_conversas WHERE notificacao_id = ? AND usuario_id = ? AND mensagem = ?";
        $stmt_check_msg = $pdo->prepare($sql_check_msg);
        $stmt_check_msg->execute([$notificacao_id, $usuario_id, $msg['mensagem']]);
        if ($stmt_check_msg->fetchColumn() == 0) {
            $sql_insert_conversa = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, ?)";
            $stmt_insert_conversa = $pdo->prepare($sql_insert_conversa);
            $stmt_insert_conversa->execute([$notificacao_id, $usuario_id, $msg['mensagem'], $tipo_usuario]);
            echo "Mensagem de '{$msg['autor']}' inserida: \"{$msg['mensagem']}\"\n";
        } else {
            echo "Mensagem de '{$msg['autor']}' já existe: \"{$msg['mensagem']}\"\n";
        }
    }
    echo "Conversa populada com sucesso!\n\n";

    // Opcional: Atualizar o status da requisição para 'em_discussao' se houver conversa
    $sql_update_req_status = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'em_discussao' WHERE id = ? AND status_notificacao = 'pendente'";
    $stmt_update_req_status = $pdo->prepare($sql_update_req_status);
    $stmt_update_req_status->execute([$requisicao_exemplo_id]);
    if ($stmt_update_req_status->rowCount() > 0) {
        echo "Status da Requisição #{$requisicao_exemplo_id} atualizado para 'em_discussao'.\n\n";
    }

    $pdo->commit();

    echo "===============================================\n";
    echo "NOTIFICAÇÕES E CONVERSAS POPULADAS COM SUCESSO!\n";
    echo "===============================================\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro ao popular notificações e conversas: " . $e->getMessage());
}

echo "</pre>";
?>

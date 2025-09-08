<?php
require_once __DIR__ . '/config/db.php';

// Silenciar erros de notice para a execução do script
error_reporting(E_ALL & ~E_NOTICE);

echo "<pre>";
echo "INICIANDO TESTE DE FLUXO DE MENSAGENS (VERSÃO SIMPLIFICADA)...\n";

// --- ETAPA 0: Encontrar usuários para o teste ---
echo "\n--- ETAPA 0: SELECIONANDO USUÁRIOS ---\n";
$admin = $pdo->query("SELECT * FROM usuarios WHERE permissao_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$requisitante = $pdo->query("SELECT * FROM usuarios WHERE permissao_id != 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$local = $pdo->query("SELECT * FROM locais LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$admin || !$requisitante || !$local) {
    die("ERRO: Não foi possível encontrar os dados necessários para o teste (Administrador, Requisitante, Local).\n");
}

echo "Administrador de Teste: " . $admin['nome'] . " (ID: " . $admin['id'] . ")\n";
echo "Requisitante de Teste: " . $requisitante['nome'] . " (ID: " . $requisitante['id'] . ")\n";
echo "Local de Teste: " . $local['nome'] . " (ID: " . $local['id'] . ")\n";

// --- ETAPA 1: Usuário cria uma requisição ---
echo "\n--- ETAPA 1: REQUISITANTE CRIA UMA NOVA REQUISIÇÃO ---\n";
$sql_req = "INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa, status_notificacao) VALUES (?, ?, NOW(), ?, 'pendente')";
$stmt_req = $pdo->prepare($sql_req);
$stmt_req->execute([$requisitante['id'], $local['id'], 'Justificativa de teste para o fluxo de mensagens.']);
$requisicao_id = $pdo->lastInsertId();
echo "Requisição de teste criada com ID: $requisicao_id\n";

// --- ETAPA 2: Sistema cria notificação para o admin ---
echo "\n--- ETAPA 2: SISTEMA GERA NOTIFICAÇÃO PARA O ADMIN ---\n";
$msg_para_admin = "Nova requisição (#$requisicao_id) criada por " . $requisitante['nome'];
$sql_notif_admin = "INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'nova_requisicao', ?, 'pendente')";
$stmt_notif_admin = $pdo->prepare($sql_notif_admin);
$stmt_notif_admin->execute([$requisicao_id, $requisitante['id'], $admin['id'], $msg_para_admin]);
$notificacao_para_admin_id = $pdo->lastInsertId();
echo "Notificação para o admin criada com ID: $notificacao_para_admin_id\n";

// --- ETAPA 3: Admin solicita mais informações ---
echo "\n--- ETAPA 3: ADMIN SOLICITA MAIS INFORMAÇÕES ---\n";
$sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'em_discussao' WHERE id = ?";
$pdo->prepare($sql_update_req)->execute([$requisicao_id]);
echo "Status da requisição #$requisicao_id atualizado para 'em_discussao'\n";

$msg_para_user = "Por favor, forneça mais detalhes sobre a urgência destes itens.";
$sql_notif_user = "INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'resposta_admin', ?, 'pendente')";
$stmt_notif_user = $pdo->prepare($sql_notif_user);
$stmt_notif_user->execute([$requisicao_id, $admin['id'], $requisitante['id'], $msg_para_user]);
$notificacao_para_user_id = $pdo->lastInsertId();
echo "Notificação para o requisitante criada com ID: $notificacao_para_user_id\n";

$sql_conversa_admin = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, 'admin')";
$stmt_conversa_admin = $pdo->prepare($sql_conversa_admin);
$stmt_conversa_admin->execute([$notificacao_para_user_id, $admin['id'], $msg_para_user]);
echo "Mensagem do admin salva no histórico da conversa.\n";

// --- ETAPA 4: Usuário responde ao admin ---
echo "\n--- ETAPA 4: REQUISITANTE RESPONDE AO ADMIN ---\n";
$sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'respondida' WHERE id = ?";
$pdo->prepare($sql_update_notif)->execute([$notificacao_para_user_id]);
echo "Status da notificação #$notificacao_para_user_id atualizado para 'respondida'\n";

$msg_resposta_user = "A urgência é alta. Os materiais são para o evento da próxima semana.";
$sql_conversa_user = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, 'requisitante')";
$stmt_conversa_user = $pdo->prepare($sql_conversa_user);
$stmt_conversa_user->execute([$notificacao_para_user_id, $requisitante['id'], $msg_resposta_user]);
echo "Resposta do requisitante salva no histórico da conversa.\n";

echo "\nTESTE CONCLUÍDO. As operações foram executadas no banco de dados.\n";
echo "</pre>";
<?php
// Retorna o número de notificações pendentes para o usuário logado (AJAX)
require_once '../config/db.php';

// Iniciar sessão apenas se não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['count' => 0];
if (isset($_SESSION['id'])) {
    // Para notificações gerais, contar apenas as com status 'Pendente'
    $sql1 = "SELECT COUNT(id) FROM notificacoes WHERE usuario_id = ? AND status = 'Pendente'";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([$_SESSION['id']]);
    $count1 = (int)$stmt1->fetchColumn();
    
    // Para notificações de movimentação, contar apenas as com status_confirmacao 'Pendente'
    $sql2 = "SELECT COUNT(id) FROM notificacoes_movimentacao WHERE usuario_notificado_id = ? AND status_confirmacao = 'Pendente'";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$_SESSION['id']]);
    $count2 = (int)$stmt2->fetchColumn();
    
    $response['count'] = $count1 + $count2;
}
echo json_encode($response);
?>

<?php
// Retorna o número de notificações pendentes para o usuário logado (AJAX)
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

$response = ['count' => 0];
if (isset($_SESSION['id'])) {
    // Atualize a consulta conforme sua tabela de notificações de movimentação
    $sql = "SELECT COUNT(id) FROM notificacoes_movimentacao WHERE usuario_notificado_id = ? AND status_confirmacao = 'Pendente'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['id']]);
    $response['count'] = (int)$stmt->fetchColumn();
}
echo json_encode($response);

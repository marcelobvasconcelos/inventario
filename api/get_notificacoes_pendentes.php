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
    // Conta diretamente os itens que pertencem ao usuário e estão pendentes
    $sql = "SELECT COUNT(id) FROM itens WHERE responsavel_id = ? AND status_confirmacao = 'Pendente'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['id']]);
    $response['count'] = (int)$stmt->fetchColumn();
}
echo json_encode($response);
?>

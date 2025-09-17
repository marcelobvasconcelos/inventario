<?php
require_once '../config/db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['id'];

try {
    // Contar requisições do usuário com status 'discussao' ou 'aprovada'
    $sql = "SELECT COUNT(*) as total FROM almoxarifado_requisicoes 
            WHERE usuario_id = ? AND status IN ('discussao', 'aprovada')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => $result['total']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>
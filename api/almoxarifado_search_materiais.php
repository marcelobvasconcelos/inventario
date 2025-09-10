<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$term = isset($_GET['term']) ? '%' . trim($_GET['term']) . '%' : '';

if (empty(trim($term, '%'))) {
    echo json_encode([]);
    exit;
}

try {
    // SQL corrigida com o nome da tabela e colunas corretas do almoxarifado
    $sql = "SELECT id, nome, estoque_atual, categoria, quantidade_maxima_requisicao
            FROM almoxarifado_materiais
            WHERE nome LIKE ? AND estoque_atual > 0 AND status = 'ativo'
            ORDER BY nome ASC
            LIMIT 15";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$term]);
    $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($materiais);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>

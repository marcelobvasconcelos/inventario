<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    $search_term = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    if (strlen($search_term) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $sql = "SELECT m.id, m.nome, m.estoque_atual, m.unidade_medida, m.quantidade_maxima_requisicao
            FROM almoxarifado_materiais m 
            WHERE m.nome LIKE ? AND m.estoque_atual > 0
            ORDER BY m.nome ASC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $search_term . '%']);
    $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar campo categoria como string vazia para compatibilidade
    foreach ($materiais as &$material) {
        $material['categoria'] = '';
    }
    
    echo json_encode($materiais);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
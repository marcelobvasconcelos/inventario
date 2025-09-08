<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(dirname(dirname(__DIR__)));

require_once $base_path . '/config/db.php';
header('Content-Type: application/json');

// Verifica se os IDs dos materiais foram enviados
$item_ids = isset($_POST['item_ids']) ? $_POST['item_ids'] : [];

if (empty($item_ids)) {
    echo json_encode([]);
    exit;
}

try {
    // Preparar placeholders para a consulta
    $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
    
    // Buscar informações dos materiais
    $sql = "SELECT m.id, m.nome, m.qtd as estoque_atual, c.descricao as categoria 
            FROM materiais m 
            LEFT JOIN categorias c ON m.categoria_id = c.id 
            WHERE m.id IN ($placeholders) AND m.qtd > 0
            ORDER BY m.nome";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($item_ids);
    $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($materiais);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar materiais: ' . $e->getMessage()]);
}
?>
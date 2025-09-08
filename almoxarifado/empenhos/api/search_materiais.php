<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(dirname(dirname(__DIR__)));

require_once $base_path . '/config/db.php';
header('Content-Type: application/json');

// Verifica se o termo de busca foi enviado
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Verificar se é uma busca por ID específico (caso especial para pré-preencher itens)
    if (strpos($term, 'item_') === 0) {
        $itemId = str_replace('item_', '', $term);
        $sql = "SELECT m.id, m.nome, m.qtd as estoque_atual, c.descricao as categoria 
                FROM materiais m 
                LEFT JOIN categorias c ON m.categoria_id = c.id 
                WHERE m.id = ? AND m.qtd > 0
                ORDER BY m.nome 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$itemId]);
        $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Busca materiais que contenham o termo pesquisado
        $sql = "SELECT m.id, m.nome, m.qtd as estoque_atual, c.descricao as categoria 
                FROM materiais m 
                LEFT JOIN categorias c ON m.categoria_id = c.id 
                WHERE m.nome LIKE ? AND m.qtd > 0
                ORDER BY m.nome 
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['%' . $term . '%']);
        $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($materiais);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar materiais: ' . $e->getMessage()]);
}
?>
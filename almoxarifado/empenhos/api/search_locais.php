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
    // Busca locais que contenham o termo pesquisado
    $sql = "SELECT id, nome FROM locais WHERE nome LIKE ? ORDER BY nome LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $term . '%']);
    $locais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($locais);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar locais: ' . $e->getMessage()]);
}
?>
<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? $_GET['term'] : '';

try {
    // Prepara a consulta para buscar locais cujo nome contenha o termo pesquisado
    $stmt = $pdo->prepare("SELECT id, nome FROM locais WHERE nome LIKE ? ORDER BY nome LIMIT 10");
    $stmt->execute(['%' . $term . '%']);
    $locais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($locais);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar locais: ' . $e->getMessage()]);
}
?>
<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT CONCAT(COALESCE(numero, CAST(id AS CHAR)), ' - ', descricao) as categoria FROM almoxarifado_categorias WHERE CONCAT(COALESCE(numero, CAST(id AS CHAR)), ' - ', descricao) LIKE ? ORDER BY descricao ASC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $query . '%']);
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($categorias);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? $_GET['term'] . '%' : '%';

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE nome LIKE ? ORDER BY nome LIMIT 10");
    $stmt->execute([$term]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($usuarios);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar usuários: ' . $e->getMessage()]);
}

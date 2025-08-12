<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? $_GET['term'] . '%' : '%';

try {
    // SQL para buscar usuários. A cláusula para 'Visualizador' foi removida pois não existe no schema do banco de dados.
    $sql = "SELECT id, nome FROM usuarios WHERE nome LIKE ? ORDER BY nome LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$term]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($usuarios);

} catch (PDOException $e) {
    // Retorna uma mensagem de erro em JSON se a busca falhar, em vez de falhar silenciosamente.
    echo json_encode(['error' => 'Erro ao buscar usuários: ' . $e->getMessage()]);
}
?>
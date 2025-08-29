<?php
header('Content-Type: application/json');

// Inclui a conexão com o banco de dados
require_once '../config/db.php';

// Verifica se o parâmetro de pesquisa foi enviado
if (!isset($_GET['q']) || strlen($_GET['q']) < 3) {
    echo json_encode([]);
    exit;
}

$termo = $_GET['q'];

try {
    // Prepara a consulta para buscar usuários
    $sql = "SELECT u.id, u.nome, u.email, u.status, p.nome as perfil_nome,
                   (SELECT COUNT(*) FROM solicitacoes_senha WHERE usuario_id = u.id AND status = 'pendente') as solicitacoes_pendentes
            FROM usuarios u
            JOIN perfis p ON u.permissao_id = p.id
            WHERE u.nome != 'Lixeira' AND u.nome LIKE ?
            ORDER BY u.nome ASC
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$termo%"]);
    $usuarios = $stmt->fetchAll();
    
    echo json_encode($usuarios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar usuários']);
}
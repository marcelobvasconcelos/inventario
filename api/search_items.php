<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

// Apenas administradores podem acessar este endpoint
if(!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador'){
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($term) || strlen($term) < 1) { // Reduzi para 1 caractere para permitir pesquisa por ID
    echo json_encode(['error' => 'Termo de busca muito curto.']);
    exit;
}

try {
    // Verificar se o termo é um número (possivelmente um ID)
    $is_numeric = is_numeric($term);
    
    // SQL para buscar itens com informações relacionadas
    if ($is_numeric) {
        // Se for numérico, pesquisa por ID, patrimônio ou nome
        $sql = "SELECT 
                    i.id, 
                    i.nome, 
                    i.patrimonio_novo, 
                    i.patrimonio_secundario, 
                    i.estado, 
                    i.status_confirmacao,
                    l.nome AS local, 
                    u.nome AS responsavel
                FROM itens i 
                JOIN locais l ON i.local_id = l.id 
                JOIN usuarios u ON i.responsavel_id = u.id
                WHERE (i.id = ? OR i.patrimonio_novo LIKE ? OR i.patrimonio_secundario LIKE ? OR i.nome LIKE ?)
                ORDER BY i.nome ASC 
                LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $searchTerm = "%{$term}%";
        $stmt->execute([$term, $searchTerm, $searchTerm, $searchTerm]);
    } else {
        // Se não for numérico, pesquisa por nome, patrimônio ou responsável
        $sql = "SELECT 
                    i.id, 
                    i.nome, 
                    i.patrimonio_novo, 
                    i.patrimonio_secundario, 
                    i.estado, 
                    i.status_confirmacao,
                    l.nome AS local, 
                    u.nome AS responsavel
                FROM itens i 
                JOIN locais l ON i.local_id = l.id 
                JOIN usuarios u ON i.responsavel_id = u.id
                WHERE (i.nome LIKE ? OR i.patrimonio_novo LIKE ? OR i.patrimonio_secundario LIKE ? OR u.nome LIKE ?)
                ORDER BY i.nome ASC 
                LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $searchTerm = "%{$term}%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($items);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar itens: ' . $e->getMessage()]);
}
?>
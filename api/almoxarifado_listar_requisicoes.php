<?php
// api/almoxarifado_listar_requisicoes.php - API para listar requisições de almoxarifado
require_once '../config/db.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se o usuário tem permissão de administrador ou almoxarife
if ($_SESSION["permissao"] != 'Administrador' && $_SESSION["permissao"] != 'Almoxarife') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores e almoxarifes podem listar todas as requisições.']);
    exit;
}

try {
    // Buscar todas as requisições com detalhes
    $stmt = $pdo->prepare("
        SELECT 
            r.id, 
            r.usuario_id, 
            r.local_id, 
            r.data_requisicao, 
            r.status, 
            r.justificativa,
            u.nome as usuario_nome,
            l.nome as local_nome
        FROM almoxarifado_requisicoes r
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN locais l ON r.local_id = l.id
        ORDER BY r.data_requisicao DESC
    ");
    $stmt->execute();
    $requisicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar os itens de cada requisição
    $stmt_itens = $pdo->prepare("
        SELECT 
            i.requisicao_id,
            i.produto_id,
            i.quantidade_solicitada,
            i.quantidade_entregue,
            p.nome as produto_nome,
            p.unidade_medida as produto_unidade
        FROM almoxarifado_requisicoes_itens i
        JOIN almoxarifado_produtos p ON i.produto_id = p.id
        WHERE i.requisicao_id = ?
    ");
    
    // Adicionar os itens a cada requisição
    foreach ($requisicoes as &$requisicao) {
        $stmt_itens->execute([$requisicao['id']]);
        $requisicao['itens'] = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Retornar sucesso
    echo json_encode(['success' => true, 'requisicoes' => $requisicoes]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar requisições: ' . $e->getMessage()]);
}
?>
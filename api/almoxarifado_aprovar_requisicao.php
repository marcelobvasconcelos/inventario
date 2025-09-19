<?php
// api/almoxarifado_aprovar_requisicao.php - API para aprovar requisições de almoxarifado
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
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores e almoxarifes podem aprovar requisições.']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter os dados do POST
$data = json_decode(file_get_contents('php://input'), true);

// Validar dados
if (!isset($data['requisicao_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da requisição não informado']);
    exit;
}

$requisicao_id = (int)$data['requisicao_id'];

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Verificar se a requisição existe e está pendente
    $stmt_check = $pdo->prepare("SELECT id, status FROM almoxarifado_requisicoes WHERE id = ?");
    $stmt_check->execute([$requisicao_id]);
    $requisicao = $stmt_check->fetch();
    
    if (!$requisicao) {
        throw new Exception("Requisição não encontrada");
    }
    
    if ($requisicao['status'] !== 'pendente') {
        throw new Exception("Requisição não está pendente");
    }
    
    // Atualizar o status da requisição para 'aprovada'
    $stmt_update = $pdo->prepare("UPDATE almoxarifado_requisicoes SET status = 'aprovada' WHERE id = ?");
    $stmt_update->execute([$requisicao_id]);
    
    // Obter os itens da requisição
    $stmt_itens = $pdo->prepare("SELECT produto_id, quantidade_solicitada FROM almoxarifado_requisicoes_itens WHERE requisicao_id = ?");
    $stmt_itens->execute([$requisicao_id]);
    $itens = $stmt_itens->fetchAll();
    
    // Atualizar o estoque dos materiais
    foreach ($itens as $item) {
        $material_id = (int)$item['produto_id'];
        $quantidade = (int)$item['quantidade_solicitada'];
        
        // Verificar se há estoque suficiente
        $stmt_check_estoque = $pdo->prepare("SELECT estoque_atual, nome FROM almoxarifado_materiais WHERE id = ?");
        $stmt_check_estoque->execute([$material_id]);
        $material = $stmt_check_estoque->fetch();
        
        if (!$material || $material['estoque_atual'] < $quantidade) {
            throw new Exception("Estoque insuficiente para o material: " . ($material['nome'] ?? "ID $material_id"));
        }
        
        // Capturar saldo anterior
        $saldo_anterior = $material['estoque_atual'];
        $saldo_atual = $saldo_anterior - $quantidade;
        
        // Atualizar o estoque
        $stmt_estoque = $pdo->prepare("UPDATE almoxarifado_materiais SET estoque_atual = estoque_atual - ? WHERE id = ?");
        $stmt_estoque->execute([$quantidade, $material_id]);
        
        // Registrar movimentação
        $stmt_mov = $pdo->prepare("INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) VALUES (?, 'saida', ?, ?, ?, NOW(), ?, ?)");
        $stmt_mov->execute([$material_id, $quantidade, $saldo_anterior, $saldo_atual, $_SESSION['id'], $requisicao_id]);
    }
    
    // Commit da transação
    $pdo->commit();
    
    // Retornar sucesso
    echo json_encode(['success' => true, 'message' => 'Requisição aprovada com sucesso']);
    
} catch (Exception $e) {
    // Rollback da transação em caso de erro
    $pdo->rollback();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao aprovar requisição: ' . $e->getMessage()]);
}
?>
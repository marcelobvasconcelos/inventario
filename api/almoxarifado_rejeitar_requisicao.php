<?php
// api/almoxarifado_rejeitar_requisicao.php - API para rejeitar requisições de almoxarifado
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
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores e almoxarifes podem rejeitar requisições.']);
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
    
    // Atualizar o status da requisição para 'rejeitada'
    $stmt_update = $pdo->prepare("UPDATE almoxarifado_requisicoes SET status = 'rejeitada' WHERE id = ?");
    $stmt_update->execute([$requisicao_id]);
    
    // Commit da transação
    $pdo->commit();
    
    // Retornar sucesso
    echo json_encode(['success' => true, 'message' => 'Requisição rejeitada com sucesso']);
    
} catch (Exception $e) {
    // Rollback da transação em caso de erro
    $pdo->rollback();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar requisição: ' . $e->getMessage()]);
}
?>
<?php
// api/almoxarifado_processar_requisicao.php - API para processar requisições de almoxarifado
require_once '../config/db.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
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
if (!isset($data['local_id']) || !isset($data['justificativa']) || !isset($data['itens'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$local_id = (int)$data['local_id'];
$justificativa = trim($data['justificativa']);
$itens = $data['itens'];

// Validar se há itens
if (empty($itens)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nenhum item na requisição']);
    exit;
}

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Inserir a requisição
    $stmt_requisicao = $pdo->prepare("INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa) VALUES (?, ?, NOW(), ?)");
    $stmt_requisicao->execute([$_SESSION['id'], $local_id, $justificativa]);
    $requisicao_id = $pdo->lastInsertId();
    
    // Inserir os itens da requisição
    $stmt_item = $pdo->prepare("INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) VALUES (?, ?, ?)");
    
    foreach ($itens as $item) {
        $produto_id = (int)$item['produto_id'];
        $quantidade = (int)$item['quantidade'];
        
        // Validar quantidade
        if ($quantidade <= 0) {
            throw new Exception("Quantidade inválida para o produto ID $produto_id");
        }
        
        $stmt_item->execute([$requisicao_id, $produto_id, $quantidade]);
    }
    
    // Commit da transação
    $pdo->commit();
    
    // Retornar sucesso
    echo json_encode(['success' => true, 'message' => 'Requisição criada com sucesso', 'requisicao_id' => $requisicao_id]);
    
} catch (Exception $e) {
    // Rollback da transação em caso de erro
    $pdo->rollback();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao criar requisição: ' . $e->getMessage()]);
}
?>
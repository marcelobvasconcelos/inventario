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

// Verificar se algum item ultrapassa a quantidade máxima
$exige_justificativa = false;
$itens_com_excesso = [];

try {
    foreach ($itens as $item) {
        $produto_id = (int)$item['produto_id'];
        $quantidade = (int)$item['quantidade'];
        
        // Validar quantidade
        if ($quantidade <= 0) {
            throw new Exception("Quantidade inválida para o produto ID $produto_id");
        }
        
        // Buscar a quantidade máxima por requisição para este material
        $stmt_max = $pdo->prepare("SELECT nome, quantidade_maxima_requisicao, estoque_atual FROM almoxarifado_materiais WHERE id = ?");
        $stmt_max->execute([$produto_id]);
        $material_info = $stmt_max->fetch(PDO::FETCH_ASSOC);
        
        if (!$material_info) {
            throw new Exception("Produto ID $produto_id não encontrado");
        }
        
        // Verificar estoque
        if ($quantidade > $material_info['estoque_atual']) {
            throw new Exception("Quantidade solicitada para " . $material_info['nome'] . " excede o estoque disponível.");
        }
        
        // Verificar quantidade máxima
        if ($material_info['quantidade_maxima_requisicao'] !== null && $quantidade > $material_info['quantidade_maxima_requisicao']) {
            $exige_justificativa = true;
            $itens_com_excesso[] = $material_info['nome'];
        }
    }
    
    // Se algum item ultrapassar a quantidade máxima, a justificativa é obrigatória
    if ($exige_justificativa && empty($justificativa)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Justificativa obrigatória para itens que ultrapassam a quantidade máxima permitida: ' . implode(', ', $itens_com_excesso)]);
        exit;
    }
    
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
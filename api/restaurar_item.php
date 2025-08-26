<?php
session_start();
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem restaurar itens
if (!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

// Ler os dados JSON do corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar dados
if (!isset($data['item_id']) || !isset($data['novo_local_id']) || !isset($data['novo_responsavel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

$item_id = (int)$data['item_id'];
$novo_local_id = (int)$data['novo_local_id'];
$novo_responsavel_id = (int)$data['novo_responsavel_id'];

try {
    // Obter o ID do usuário "Lixeira"
    $stmt_lixeira = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt_lixeira->execute();
    $lixeira = $stmt_lixeira->fetch(PDO::FETCH_ASSOC);
    
    if (!$lixeira) {
        echo json_encode(['success' => false, 'message' => 'Usuário "Lixeira" não encontrado.']);
        exit;
    }
    
    $lixeira_id = $lixeira['id'];
    
    // Verificar se o item está realmente na lixeira e excluído
    $stmt_check = $pdo->prepare("SELECT id FROM itens WHERE id = ? AND responsavel_id = ? AND estado = 'Excluido'");
    $stmt_check->execute([$item_id, $lixeira_id]);
    
    if (!$stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Item não encontrado na lixeira.']);
        exit;
    }
    
    // Verificar se o novo local existe
    $stmt_local = $pdo->prepare("SELECT id FROM locais WHERE id = ?");
    $stmt_local->execute([$novo_local_id]);
    
    if (!$stmt_local->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Local inválido.']);
        exit;
    }
    
    // Verificar se o novo responsável existe
    $stmt_usuario = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt_usuario->execute([$novo_responsavel_id]);
    
    if (!$stmt_usuario->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Responsável inválido.']);
        exit;
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Restaurar o item (mudar estado e atribuir novo responsável e local)
    $stmt_update = $pdo->prepare("UPDATE itens SET estado = 'Em uso', responsavel_id = ?, local_id = ? WHERE id = ?");
    $stmt_update->execute([$novo_responsavel_id, $novo_local_id, $item_id]);
    
    // Registrar a movimentação
    $stmt_mov = $pdo->prepare("INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id, usuario_destino_id) 
                               SELECT ?, local_id, ?, ?, responsavel_id, ? FROM itens WHERE id = ?");
    $stmt_mov->execute([$item_id, $novo_local_id, $_SESSION['id'], $novo_responsavel_id, $item_id]);
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Item restaurado com sucesso!']);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erro ao restaurar item: ' . $e->getMessage()]);
}
?>
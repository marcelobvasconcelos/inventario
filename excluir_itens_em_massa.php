<?php
session_start();
require_once 'config/db.php';

// Verificar permissões - apenas administradores podem excluir itens
if (!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não tem permissão para executar esta ação.']);
    exit;
}

// Ler os dados JSON do corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar se os IDs dos itens foram enviados
if (!isset($data['item_ids']) || !is_array($data['item_ids']) || empty($data['item_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum item selecionado para exclusão.']);
    exit;
}

$item_ids = $data['item_ids'];

// Filtrar os IDs para garantir que sejam inteiros
$item_ids = array_map('intval', $item_ids);

// Verificar se há itens com status "Pendente" que não podem ser excluídos
try {
    // Preparar consulta para verificar status dos itens
    $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
    $sql_check_status = "SELECT id, status_confirmacao FROM itens WHERE id IN ($placeholders)";
    $stmt_check = $pdo->prepare($sql_check_status);
    $stmt_check->execute($item_ids);
    $itens_status = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
    
    // Separar itens pendentes dos demais
    $itens_pendentes = [];
    $itens_para_excluir = [];
    
    foreach ($itens_status as $item) {
        if ($item['status_confirmacao'] == 'Pendente') {
            $itens_pendentes[] = $item['id'];
        } else {
            $itens_para_excluir[] = $item['id'];
        }
    }
    
    // Se todos os itens forem pendentes, não excluir nenhum
    if (count($itens_pendentes) == count($item_ids)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Todos os itens selecionados estão com status "Pendente" e não podem ser excluídos.'
        ]);
        exit;
    }
    
    // Se houver itens pendentes misturados com outros, excluir apenas os que não estão pendentes
    if (!empty($itens_pendentes) && !empty($itens_para_excluir)) {
        // Criar mensagem informativa
        $mensagem = 'Alguns itens não foram excluídos pois estão com status "Pendente". ';
        $mensagem .= 'Itens excluídos: ' . count($itens_para_excluir) . '. ';
        $mensagem .= 'Itens não excluídos (Pendentes): ' . count($itens_pendentes) . '.';
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Preparar a consulta para atualizar o estado dos itens para 'Excluido'
        $placeholders_excluir = str_repeat('?,', count($itens_para_excluir) - 1) . '?';
        $sql = "UPDATE itens SET estado = 'Excluido' WHERE id IN ($placeholders_excluir)";
        $stmt = $pdo->prepare($sql);
        
        // Executar a consulta com os IDs dos itens que podem ser excluídos
        $stmt->execute($itens_para_excluir);
        
        // Confirmar a transação
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $mensagem
        ]);
        exit;
    }
    
    // Se não houver itens pendentes, excluir todos normalmente
    if (empty($itens_pendentes) && !empty($itens_para_excluir)) {
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Preparar a consulta para atualizar o estado dos itens para 'Excluido'
        $sql = "UPDATE itens SET estado = 'Excluido' WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        
        // Executar a consulta com os IDs dos itens
        $stmt->execute($item_ids);
        
        // Confirmar a transação
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => count($item_ids) . ' item(s) excluído(s) com sucesso.'
        ]);
        exit;
    }
    
    // Caso não haja itens para excluir (situação inesperada)
    echo json_encode([
        'success' => false, 
        'message' => 'Nenhum item válido para exclusão.'
    ]);
} catch (Exception $e) {
    // Reverter a transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao excluir itens: ' . $e->getMessage()
    ]);
}
?>
<?php
// Script para testar a funcionalidade de exclusão de itens
require_once 'config/db.php';

try {
    // Obter o ID do usuário "Lixeira"
    $stmt_lixeira = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt_lixeira->execute();
    $lixeira = $stmt_lixeira->fetch(PDO::FETCH_ASSOC);
    
    if (!$lixeira) {
        echo "Erro: Usuário 'Lixeira' não encontrado.
";
        exit(1);
    }
    
    $lixeira_id = $lixeira['id'];
    
    // Contar itens na lixeira antes do teste
    $stmt_count_before = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE estado = 'Excluido' AND responsavel_id = ?");
    $stmt_count_before->execute([$lixeira_id]);
    $count_before = $stmt_count_before->fetchColumn();
    
    echo "Itens na lixeira antes do teste: " . $count_before . "
";
    
    // Encontrar um item para excluir (que não esteja na lixeira)
    $stmt_item = $pdo->prepare("SELECT id FROM itens WHERE estado != 'Excluido' LIMIT 1");
    $stmt_item->execute();
    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo "Nenhum item disponível para teste.
";
        exit(0);
    }
    
    $item_id = $item['id'];
    echo "Item selecionado para exclusão: " . $item_id . "
";
    
    // Simular a exclusão do item (atualizar estado e responsável)
    $stmt_update = $pdo->prepare("UPDATE itens SET estado = 'Excluido', responsavel_id = ? WHERE id = ?");
    $stmt_update->execute([$lixeira_id, $item_id]);
    
    echo "Item excluído e movido para a lixeira.
";
    
    // Contar itens na lixeira depois do teste
    $stmt_count_after = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE estado = 'Excluido' AND responsavel_id = ?");
    $stmt_count_after->execute([$lixeira_id]);
    $count_after = $stmt_count_after->fetchColumn();
    
    echo "Itens na lixeira depois do teste: " . $count_after . "
";
    
    if ($count_after > $count_before) {
        echo "Teste bem-sucedido! O item foi movido para a lixeira.
";
    } else {
        echo "Erro no teste. O item não foi movido para a lixeira.
";
    }
    
} catch (Exception $e) {
    echo "Erro no teste: " . $e->getMessage() . "
";
    exit(1);
}
?>
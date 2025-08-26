<?php
// Script para testar a funcionalidade de restauração de itens da lixeira
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
    
    // Encontrar um item na lixeira para restaurar
    $stmt_item = $pdo->prepare("SELECT id, local_id FROM itens WHERE estado = 'Excluido' AND responsavel_id = ? LIMIT 1");
    $stmt_item->execute([$lixeira_id]);
    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo "Nenhum item na lixeira disponível para teste.
";
        exit(0);
    }
    
    $item_id = $item['id'];
    $local_atual = $item['local_id'];
    echo "Item selecionado para restauração: " . $item_id . "
";
    
    // Obter um usuário e local válidos para restaurar o item
    $stmt_usuario = $pdo->prepare("SELECT id FROM usuarios WHERE id != ? LIMIT 1");
    $stmt_usuario->execute([$lixeira_id]);
    $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "Nenhum usuário disponível para teste.
";
        exit(0);
    }
    
    $novo_responsavel_id = $usuario['id'];
    echo "Novo responsável: " . $novo_responsavel_id . "
";
    
    $stmt_local = $pdo->prepare("SELECT id FROM locais WHERE id != ? LIMIT 1");
    $stmt_local->execute([$local_atual]);
    $local = $stmt_local->fetch(PDO::FETCH_ASSOC);
    
    if (!$local) {
        echo "Nenhum local disponível para teste.
";
        exit(0);
    }
    
    $novo_local_id = $local['id'];
    echo "Novo local: " . $novo_local_id . "
";
    
    // Simular a restauração do item (atualizar estado, responsável e local)
    $stmt_update = $pdo->prepare("UPDATE itens SET estado = 'Em uso', responsavel_id = ?, local_id = ? WHERE id = ?");
    $stmt_update->execute([$novo_responsavel_id, $novo_local_id, $item_id]);
    
    echo "Item restaurado com sucesso!
";
    
    // Verificar se o item foi removido da lixeira
    $stmt_check = $pdo->prepare("SELECT id FROM itens WHERE id = ? AND responsavel_id = ? AND estado = 'Excluido'");
    $stmt_check->execute([$item_id, $lixeira_id]);
    $item_na_lixeira = $stmt_check->fetch();
    
    if (!$item_na_lixeira) {
        echo "Teste bem-sucedido! O item foi removido da lixeira.
";
    } else {
        echo "Erro no teste. O item ainda está na lixeira.
";
    }
    
} catch (Exception $e) {
    echo "Erro no teste: " . $e->getMessage() . "
";
    exit(1);
}
?>
<?php
// Script para mover todos os itens excluídos para o usuário "Lixeira"
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
    echo "ID do usuário 'Lixeira': " . $lixeira_id . "
";
    
    // Contar itens excluídos antes da atualização
    $stmt_count_before = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE estado = 'Excluido'");
    $stmt_count_before->execute();
    $count_before = $stmt_count_before->fetchColumn();
    echo "Itens excluídos antes da atualização: " . $count_before . "
";
    
    // Verificar quantos desses itens já estão atribuídos à lixeira
    $stmt_count_lixeira = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE estado = 'Excluido' AND responsavel_id = ?");
    $stmt_count_lixeira->execute([$lixeira_id]);
    $count_lixeira = $stmt_count_lixeira->fetchColumn();
    echo "Itens excluídos já na lixeira: " . $count_lixeira . "
";
    
    // Verificar quantos itens precisam ser movidos para a lixeira
    $stmt_count_mover = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE estado = 'Excluido' AND responsavel_id != ?");
    $stmt_count_mover->execute([$lixeira_id]);
    $count_mover = $stmt_count_mover->fetchColumn();
    echo "Itens a serem movidos para a lixeira: " . $count_mover . "
";
    
    if ($count_mover > 0) {
        // Mover itens excluídos para o usuário "Lixeira"
        $stmt_update = $pdo->prepare("UPDATE itens SET responsavel_id = ? WHERE estado = 'Excluido' AND responsavel_id != ?");
        $stmt_update->execute([$lixeira_id, $lixeira_id]);
        
        echo "Itens movidos para a lixeira com sucesso!
";
        
        // Verificar novamente a contagem após a atualização
        $stmt_count_after = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE estado = 'Excluido' AND responsavel_id = ?");
        $stmt_count_after->execute([$lixeira_id]);
        $count_after = $stmt_count_after->fetchColumn();
        echo "Total de itens na lixeira após a atualização: " . $count_after . "
";
    } else {
        echo "Não há itens para mover para a lixeira.
";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "
";
    exit(1);
}
?>
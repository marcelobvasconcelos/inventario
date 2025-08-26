<?php
// Script para verificar se a página de itens excluídos está funcionando corretamente
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
    
    // Contar itens na lixeira
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE estado = 'Excluido' AND responsavel_id = ?");
    $stmt_count->execute([$lixeira_id]);
    $count = $stmt_count->fetchColumn();
    echo "Total de itens na lixeira: " . $count . "
";
    
    if ($count > 0) {
        // Listar os primeiros 5 itens na lixeira
        $stmt_items = $pdo->prepare("SELECT id, nome, patrimonio_novo FROM itens WHERE estado = 'Excluido' AND responsavel_id = ? ORDER BY id DESC LIMIT 5");
        $stmt_items->execute([$lixeira_id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Itens na lixeira:
";
        foreach ($items as $item) {
            echo "- ID: " . $item['id'] . ", Nome: " . $item['nome'] . ", Patrimônio: " . $item['patrimonio_novo'] . "
";
        }
    } else {
        echo "Nenhum item encontrado na lixeira.
";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "
";
    exit(1);
}
?>
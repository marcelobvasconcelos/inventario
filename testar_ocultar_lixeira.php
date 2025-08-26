<?php
// Script para verificar se o usuário "Lixeira" está oculto da listagem de usuários
require_once 'config/db.php';

try {
    // Verificar se o usuário "Lixeira" existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt->execute();
    $lixeira = $stmt->fetchColumn();
    
    if (!$lixeira) {
        echo "Usuário 'Lixeira' não encontrado.
";
        exit(1);
    }
    
    echo "Usuário 'Lixeira' encontrado com ID: " . $lixeira . "
";
    
    // Verificar a contagem de usuários excluindo a lixeira
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM usuarios u JOIN perfis p ON u.permissao_id = p.id WHERE u.nome != 'Lixeira'");
    $stmt_count->execute();
    $count_sem_lixeira = $stmt_count->fetchColumn();
    
    echo "Total de usuários (excluindo Lixeira): " . $count_sem_lixeira . "
";
    
    // Verificar a contagem total de usuários
    $stmt_count_total = $pdo->prepare("SELECT COUNT(*) FROM usuarios u JOIN perfis p ON u.permissao_id = p.id");
    $stmt_count_total->execute();
    $count_total = $stmt_count_total->fetchColumn();
    
    echo "Total de usuários (incluindo Lixeira): " . $count_total . "
";
    
    if ($count_total > $count_sem_lixeira) {
        echo "Teste bem-sucedido! O usuário 'Lixeira' está sendo oculto da listagem.
";
    } else {
        echo "Erro no teste. O usuário 'Lixeira' não está sendo oculto da listagem.
";
    }
    
} catch (Exception $e) {
    echo "Erro no teste: " . $e->getMessage() . "
";
    exit(1);
}
?>
<?php
// Script para atualizar o banco de dados e adicionar o campo 'excluido' à tabela 'itens'

require_once 'config/db.php';

try {
    // Verifica se o campo já existe
    $sql_check = "SHOW COLUMNS FROM itens LIKE 'excluido'";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() == 0) {
        // O campo não existe, então vamos adicioná-lo
        $sql_add_column = "ALTER TABLE itens ADD COLUMN excluido TINYINT(1) NOT NULL DEFAULT 0";
        $pdo->exec($sql_add_column);
        echo "Campo 'excluido' adicionado com sucesso à tabela 'itens'.
";
    } else {
        echo "Campo 'excluido' já existe na tabela 'itens'.
";
    }
} catch (Exception $e) {
    echo "Erro ao adicionar o campo 'excluido': " . $e->getMessage() . "
";
}
?>
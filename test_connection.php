<?php
// test_connection.php - Script para testar conexão e executar comandos simples
require_once 'config/db.php';

// Testar conexão
echo "<h2>Testando conexão com o banco de dados...</h2>";

if ($link) {
    echo "<p style='color: green;'>✓ Conexão estabelecida com sucesso!</p>";
    
    // Testar um comando simples
    $sql = "SHOW TABLES LIKE 'almoxarifado_requisicoes'";
    $result = mysqli_query($link, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Tabela 'almoxarifado_requisicoes' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabela 'almoxarifado_requisicoes' NÃO existe</p>";
    }
    
    // Tentar criar uma tabela simples para teste
    $sql_create = "CREATE TABLE IF NOT EXISTS teste_notificacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL
    )";
    
    if (mysqli_query($link, $sql_create)) {
        echo "<p style='color: green;'>✓ Tabela de teste criada com sucesso</p>";
        
        // Excluir a tabela de teste
        $sql_drop = "DROP TABLE teste_notificacoes";
        if (mysqli_query($link, $sql_drop)) {
            echo "<p style='color: green;'>✓ Tabela de teste excluída com sucesso</p>";
        } else {
            echo "<p style='color: red;'>✗ Erro ao excluir tabela de teste: " . mysqli_error($link) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Erro ao criar tabela de teste: " . mysqli_error($link) . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Falha na conexão com o banco de dados</p>";
}

mysqli_close($link);
?>
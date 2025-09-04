<?php
// execute_sql.php - Script para executar comandos SQL manualmente
require_once 'config/db.php';

// Ler o arquivo SQL
$sql_content = file_get_contents('almoxarifado/create_notificacoes_requisicoes_table.sql');

// Dividir em comandos individuais
$queries = explode(';', $sql_content);

echo "<h2>Executando comandos SQL...</h2>";

foreach ($queries as $query) {
    $query = trim($query);
    
    // Ignorar linhas vazias e comentários
    if (empty($query) || strpos($query, '--') === 0) {
        continue;
    }
    
    echo "<p>Executando: <code>" . htmlspecialchars(substr($query, 0, 100)) . "...</code></p>";
    
    if (mysqli_query($link, $query)) {
        echo "<p style='color: green;'>✓ Sucesso!</p>";
    } else {
        echo "<p style='color: red;'>✗ Erro: " . mysqli_error($link) . "</p>";
    }
}

mysqli_close($link);
?>
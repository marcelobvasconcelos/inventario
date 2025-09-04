<?php
// check_tables.php - Script para verificar se as tabelas foram criadas
require_once 'config/db.php';

$tables = [
    'almoxarifado_requisicoes_notificacoes',
    'almoxarifado_requisicoes_conversas',
    'almoxarifado_agendamentos'
];

echo "<h2>Verificação de Tabelas do Sistema de Notificações</h2>";

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($link, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Tabela '$table' existe</p>";
        
        // Verificar estrutura da tabela
        $sql_structure = "DESCRIBE $table";
        $result_structure = mysqli_query($link, $sql_structure);
        
        echo "<ul>";
        while ($row = mysqli_fetch_assoc($result_structure)) {
            echo "<li>{$row['Field']} - {$row['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Tabela '$table' NÃO existe</p>";
    }
}

// Verificar se a coluna status_notificacao foi adicionada à tabela almoxarifado_requisicoes
$sql = "SHOW COLUMNS FROM almoxarifado_requisicoes LIKE 'status_notificacao'";
$result = mysqli_query($link, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ Coluna 'status_notificacao' adicionada à tabela 'almoxarifado_requisicoes'</p>";
} else {
    echo "<p style='color: red;'>✗ Coluna 'status_notificacao' NÃO foi adicionada à tabela 'almoxarifado_requisicoes'</p>";
}

mysqli_close($link);
?>
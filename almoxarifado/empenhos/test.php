<?php
// Teste do módulo de empenhos
require_once '../config/db.php';

echo "<h1>Teste do Módulo de Empenhos</h1>";

// Testar conexão com o banco de dados
try {
    $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Conexão com o banco de dados: OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro na conexão com o banco de dados: " . $e->getMessage() . "</p>";
    exit;
}

// Testar se as tabelas existem
$tables = ['categorias', 'empenhos_insumos', 'notas_fiscais', 'materiais'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p style='color: green;'>✓ Tabela $table: OK ($count registros)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Erro ao acessar a tabela $table: " . $e->getMessage() . "</p>";
    }
}

echo "<p>Teste concluído.</p>";
?>
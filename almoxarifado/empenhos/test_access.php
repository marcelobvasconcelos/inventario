<?php
// Script para testar o acesso ao módulo de empenhos
session_start();

// Definir variáveis de sessão para simular um usuário logado
$_SESSION["loggedin"] = true;
$_SESSION["permissao"] = "Administrador";
$_SESSION["id"] = 1;
$_SESSION["nome"] = "Administrador Teste";

// Incluir os arquivos necessários
require_once '../../../config/db.php';

// Testar se as tabelas existem
$tables = ['categorias', 'empenhos_insumos', 'notas_fiscais', 'materiais'];
echo "<h1>Teste do Módulo de Empenhos</h1>";

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

// Testar se podemos incluir os arquivos do módulo
echo "<h2>Teste de Inclusão de Arquivos</h2>";

$files = [
    'index.php',
    'categoria_add.php',
    'categoria_edit.php',
    'empenho_add.php',
    'empenho_edit.php',
    'nota_fiscal_add.php',
    'material_add.php'
];

foreach ($files as $file) {
    $filepath = $file;
    if (file_exists($filepath)) {
        echo "<p style='color: green;'>✓ Arquivo $file: OK</p>";
    } else {
        echo "<p style='color: red;'>✗ Arquivo $file: Não encontrado</p>";
    }
}
?>
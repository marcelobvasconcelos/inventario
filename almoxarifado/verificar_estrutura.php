<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    exit;
}

echo "<h3>Verificando Estrutura da Tabela</h3>";

try {
    // Verificar estrutura da tabela almoxarifado_requisicoes_itens
    echo "<h4>Estrutura da tabela almoxarifado_requisicoes_itens:</h4>";
    $sql_structure = "DESCRIBE almoxarifado_requisicoes_itens";
    $stmt_structure = $pdo->prepare($sql_structure);
    $stmt_structure->execute();
    $columns = $stmt_structure->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $has_produto_id = false;
    $has_material_id = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($column['Field'] == 'produto_id') $has_produto_id = true;
        if ($column['Field'] == 'material_id') $has_material_id = true;
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h4>Diagnóstico:</h4>";
    
    if ($has_produto_id) {
        echo "<p>✓ Tabela usa <strong>produto_id</strong></p>";
        echo "<p>📝 Use este código no requisicao.php:</p>";
        echo "<pre>INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada)</pre>";
    } elseif ($has_material_id) {
        echo "<p>✓ Tabela usa <strong>material_id</strong></p>";
        echo "<p>📝 Use este código no requisicao.php:</p>";
        echo "<pre>INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, material_id, quantidade_solicitada)</pre>";
    } else {
        echo "<p>❌ Nenhuma coluna produto_id ou material_id encontrada!</p>";
        echo "<p>🔧 A tabela precisa ser criada ou corrigida.</p>";
    }
    
    // Verificar se a tabela existe
    echo "<h4>Verificando se as tabelas existem:</h4>";
    $tables_to_check = ['almoxarifado_requisicoes', 'almoxarifado_requisicoes_itens', 'almoxarifado_materiais'];
    
    foreach ($tables_to_check as $table) {
        $sql_check = "SHOW TABLES LIKE '$table'";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute();
        $exists = $stmt_check->rowCount() > 0;
        
        if ($exists) {
            echo "<p>✓ Tabela <strong>$table</strong> existe</p>";
        } else {
            echo "<p>❌ Tabela <strong>$table</strong> NÃO existe</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Erro: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php' class='btn btn-secondary'>Voltar</a></p>";
?>
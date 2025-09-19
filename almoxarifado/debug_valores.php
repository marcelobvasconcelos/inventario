<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    exit;
}

echo "<h3>Debug - Valores dos Materiais</h3>";

try {
    // Verificar estrutura da tabela
    echo "<h4>1. Estrutura da tabela almoxarifado_materiais:</h4>";
    $sql_structure = "DESCRIBE almoxarifado_materiais";
    $stmt_structure = $pdo->prepare($sql_structure);
    $stmt_structure->execute();
    $columns = $stmt_structure->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar dados dos materiais
    echo "<h4>2. Dados dos materiais (primeiros 5):</h4>";
    $sql_data = "SELECT id, codigo, nome, estoque_atual, valor_unitario, nota_fiscal FROM almoxarifado_materiais LIMIT 5";
    $stmt_data = $pdo->prepare($sql_data);
    $stmt_data->execute();
    $materiais = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Código</th><th>Nome</th><th>Estoque</th><th>Valor Unit.</th><th>Nota Fiscal</th></tr>";
    foreach ($materiais as $material) {
        echo "<tr>";
        echo "<td>" . $material['id'] . "</td>";
        echo "<td>" . $material['codigo'] . "</td>";
        echo "<td>" . $material['nome'] . "</td>";
        echo "<td>" . $material['estoque_atual'] . "</td>";
        echo "<td>" . $material['valor_unitario'] . "</td>";
        echo "<td>" . ($material['nota_fiscal'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar entradas
    echo "<h4>3. Últimas entradas registradas:</h4>";
    $sql_entradas = "SELECT e.*, m.nome as material_nome FROM almoxarifado_entradas e 
                     JOIN almoxarifado_materiais m ON e.material_id = m.id 
                     ORDER BY e.data_cadastro DESC LIMIT 5";
    $stmt_entradas = $pdo->prepare($sql_entradas);
    $stmt_entradas->execute();
    $entradas = $stmt_entradas->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Material</th><th>Quantidade</th><th>Valor Unit.</th><th>Data</th></tr>";
    foreach ($entradas as $entrada) {
        echo "<tr>";
        echo "<td>" . $entrada['material_nome'] . "</td>";
        echo "<td>" . $entrada['quantidade'] . "</td>";
        echo "<td>" . $entrada['valor_unitario'] . "</td>";
        echo "<td>" . $entrada['data_entrada'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar se há materiais com valor zero
    echo "<h4>4. Materiais com valor unitário zero ou NULL:</h4>";
    $sql_zero = "SELECT id, codigo, nome, estoque_atual, valor_unitario FROM almoxarifado_materiais 
                 WHERE valor_unitario IS NULL OR valor_unitario = 0";
    $stmt_zero = $pdo->prepare($sql_zero);
    $stmt_zero->execute();
    $materiais_zero = $stmt_zero->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de materiais com valor zero/NULL: " . count($materiais_zero) . "</p>";
    
    if (count($materiais_zero) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Código</th><th>Nome</th><th>Estoque</th><th>Valor Unit.</th></tr>";
        foreach ($materiais_zero as $material) {
            echo "<tr>";
            echo "<td>" . $material['id'] . "</td>";
            echo "<td>" . $material['codigo'] . "</td>";
            echo "<td>" . $material['nome'] . "</td>";
            echo "<td>" . $material['estoque_atual'] . "</td>";
            echo "<td>" . ($material['valor_unitario'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Erro: " . $e->getMessage() . "</p>";
}

echo "<p><a href='atualizar_valores_materiais.php' class='btn btn-primary'>Atualizar Valores</a></p>";
echo "<p><a href='index.php' class='btn btn-secondary'>Voltar</a></p>";
?>
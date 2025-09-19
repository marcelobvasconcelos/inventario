<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    exit;
}

echo "<h3>Corrigindo Estrutura do Banco de Dados</h3>";

try {
    // 1. Verificar se a constraint existe e removê-la
    echo "<p>1. Verificando constraints existentes...</p>";
    
    $sql_check = "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'almoxarifado_requisicoes_itens' 
                  AND CONSTRAINT_NAME LIKE '%ibfk%'";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute();
    $constraints = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($constraints as $constraint) {
        try {
            $sql_drop = "ALTER TABLE almoxarifado_requisicoes_itens DROP FOREIGN KEY `$constraint`";
            $pdo->exec($sql_drop);
            echo "<p>✓ Constraint '$constraint' removida.</p>";
        } catch (Exception $e) {
            echo "<p>⚠ Erro ao remover constraint '$constraint': " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Verificar se a coluna produto_id existe e renomeá-la
    echo "<p>2. Verificando estrutura da tabela...</p>";
    
    $sql_columns = "SHOW COLUMNS FROM almoxarifado_requisicoes_itens LIKE 'produto_id'";
    $stmt_columns = $pdo->prepare($sql_columns);
    $stmt_columns->execute();
    $has_produto_id = $stmt_columns->rowCount() > 0;
    
    if ($has_produto_id) {
        try {
            $sql_rename = "ALTER TABLE almoxarifado_requisicoes_itens CHANGE produto_id material_id INT(11) NOT NULL";
            $pdo->exec($sql_rename);
            echo "<p>✓ Coluna 'produto_id' renomeada para 'material_id'.</p>";
        } catch (Exception $e) {
            echo "<p>⚠ Erro ao renomear coluna: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>✓ Coluna 'material_id' já existe.</p>";
    }
    
    // 3. Adicionar as constraints corretas
    echo "<p>3. Adicionando constraints corretas...</p>";
    
    try {
        $sql_fk1 = "ALTER TABLE almoxarifado_requisicoes_itens 
                    ADD CONSTRAINT almoxarifado_requisicoes_itens_ibfk_1 
                    FOREIGN KEY (requisicao_id) REFERENCES almoxarifado_requisicoes(id) ON DELETE CASCADE";
        $pdo->exec($sql_fk1);
        echo "<p>✓ Foreign key para requisicao_id adicionada.</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Constraint requisicao_id já existe ou erro: " . $e->getMessage() . "</p>";
    }
    
    try {
        $sql_fk2 = "ALTER TABLE almoxarifado_requisicoes_itens 
                    ADD CONSTRAINT almoxarifado_requisicoes_itens_ibfk_2 
                    FOREIGN KEY (material_id) REFERENCES almoxarifado_materiais(id) ON DELETE CASCADE";
        $pdo->exec($sql_fk2);
        echo "<p>✓ Foreign key para material_id adicionada.</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Constraint material_id já existe ou erro: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>✓ Correção do banco de dados concluída!</strong></p>";
    echo "<p><a href='requisicao.php' class='btn btn-success'>Testar Requisição</a></p>";
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Erro geral: " . $e->getMessage() . "</p>";
}
?>
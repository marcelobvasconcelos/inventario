<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    exit;
}

echo "<h3>Sincronizando Estoque e Valores</h3>";

try {
    $pdo->beginTransaction();
    
    // 1. Recalcular estoque atual baseado nas entradas
    echo "<p>1. Recalculando estoque atual...</p>";
    
    $sql_recalc_estoque = "
        UPDATE almoxarifado_materiais m 
        SET estoque_atual = (
            SELECT COALESCE(SUM(e.quantidade), 0) 
            FROM almoxarifado_entradas e 
            WHERE e.material_id = m.id
        ) - (
            SELECT COALESCE(SUM(mov.quantidade), 0) 
            FROM almoxarifado_movimentacoes mov 
            WHERE mov.material_id = m.id AND mov.tipo = 'saida'
        )";
    
    $stmt_recalc = $pdo->prepare($sql_recalc_estoque);
    $stmt_recalc->execute();
    $materiais_atualizados = $stmt_recalc->rowCount();
    
    echo "<p>✓ Estoque recalculado para {$materiais_atualizados} materiais.</p>";
    
    // 2. Atualizar valor unitário com base na última entrada
    echo "<p>2. Atualizando valores unitários...</p>";
    
    $sql_materiais = "SELECT id FROM almoxarifado_materiais";
    $stmt_materiais = $pdo->prepare($sql_materiais);
    $stmt_materiais->execute();
    $materiais = $stmt_materiais->fetchAll(PDO::FETCH_COLUMN);
    
    $valores_atualizados = 0;
    
    foreach ($materiais as $material_id) {
        // Buscar última entrada com valor
        $sql_ultima_entrada = "SELECT valor_unitario FROM almoxarifado_entradas 
                              WHERE material_id = ? AND valor_unitario > 0 
                              ORDER BY data_cadastro DESC LIMIT 1";
        $stmt_entrada = $pdo->prepare($sql_ultima_entrada);
        $stmt_entrada->execute([$material_id]);
        $valor = $stmt_entrada->fetchColumn();
        
        if ($valor) {
            $sql_update_valor = "UPDATE almoxarifado_materiais SET valor_unitario = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update_valor);
            $stmt_update->execute([$valor, $material_id]);
            $valores_atualizados++;
        }
    }
    
    echo "<p>✓ Valores unitários atualizados para {$valores_atualizados} materiais.</p>";
    
    $pdo->commit();
    
    echo "<hr>";
    echo "<p><strong>✓ Sincronização concluída com sucesso!</strong></p>";
    echo "<p><a href='index.php' class='btn btn-success'>Ver Estoque Atualizado</a></p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p class='alert alert-danger'>Erro: " . $e->getMessage() . "</p>";
}
?>
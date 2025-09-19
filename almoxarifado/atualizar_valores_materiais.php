<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    exit;
}

echo "<h3>Atualizando Valores Unitários dos Materiais</h3>";

try {
    // Buscar materiais sem valor unitário ou com valor zero
    $sql_materiais_sem_valor = "SELECT id, nome, valor_unitario FROM almoxarifado_materiais WHERE valor_unitario IS NULL OR valor_unitario = 0";
    $stmt_materiais = $pdo->prepare($sql_materiais_sem_valor);
    $stmt_materiais->execute();
    $materiais_sem_valor = $stmt_materiais->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Encontrados " . count($materiais_sem_valor) . " materiais sem valor unitário.</p>";
    
    $atualizados = 0;
    
    foreach ($materiais_sem_valor as $material) {
        // Buscar a entrada mais recente deste material
        $sql_ultima_entrada = "SELECT valor_unitario FROM almoxarifado_entradas WHERE material_id = ? ORDER BY data_cadastro DESC LIMIT 1";
        $stmt_entrada = $pdo->prepare($sql_ultima_entrada);
        $stmt_entrada->execute([$material['id']]);
        $entrada = $stmt_entrada->fetch(PDO::FETCH_ASSOC);
        
        if ($entrada && $entrada['valor_unitario'] > 0) {
            // Atualizar o valor unitário do material
            $sql_update = "UPDATE almoxarifado_materiais SET valor_unitario = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$entrada['valor_unitario'], $material['id']]);
            
            echo "<p>✓ Material '{$material['nome']}' atualizado com valor R$ " . number_format($entrada['valor_unitario'], 2, ',', '.') . "</p>";
            $atualizados++;
        } else {
            echo "<p>⚠ Material '{$material['nome']}' não possui entradas registradas.</p>";
        }
    }
    
    echo "<hr>";
    echo "<p><strong>Resumo: {$atualizados} materiais foram atualizados com sucesso!</strong></p>";
    
    if ($atualizados > 0) {
        echo "<p><a href='index.php' class='btn btn-success'>Voltar ao Almoxarifado</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='alert alert-danger'>Erro: " . $e->getMessage() . "</p>";
}
?>
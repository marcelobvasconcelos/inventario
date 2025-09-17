<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: material_add.php");
    exit;
}

$id = $_GET['id'];

// Buscar o material
$sql_material = "SELECT * FROM almoxarifado_materiais WHERE id = ?";
$stmt_material = $pdo->prepare($sql_material);
$stmt_material->execute([$id]);
$material = $stmt_material->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    header("Location: material_add.php");
    exit;
}

// Verificar se o estoque atual é zero
if ($material['estoque_atual'] > 0) {
    header("Location: material_add.php?error=" . urlencode("Não é possível excluir o material '{$material['nome']}' porque possui estoque atual de {$material['estoque_atual']} {$material['unidade_medida']}. Zere o estoque antes de excluir."));
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Excluir movimentações relacionadas
    $sql_delete_mov = "DELETE FROM almoxarifado_movimentacoes WHERE material_id = ?";
    $stmt_delete_mov = $pdo->prepare($sql_delete_mov);
    $stmt_delete_mov->execute([$id]);
    
    // Excluir entradas relacionadas
    $sql_delete_ent = "DELETE FROM almoxarifado_entradas WHERE material_id = ?";
    $stmt_delete_ent = $pdo->prepare($sql_delete_ent);
    $stmt_delete_ent->execute([$id]);
    
    // Excluir material
    $sql_delete = "DELETE FROM almoxarifado_materiais WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$id]);
    
    $pdo->commit();
    
    header("Location: material_add.php?success=" . urlencode("Material '{$material['nome']}' e todo seu histórico foram excluídos com sucesso."));
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: material_add.php?error=" . urlencode("Erro ao excluir material: " . $e->getMessage()));
    exit;
}
?>
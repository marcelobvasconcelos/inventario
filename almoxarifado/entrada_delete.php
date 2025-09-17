<?php
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    header('Location: ../index.php');
    exit;
}

// Verificar se foi passado um ID de entrada
if(!isset($_GET['id']) || empty($_GET['id'])){
    header('Location: index.php');
    exit;
}

$entrada_id = (int)$_GET['id'];
$volta = isset($_GET['volta']) ? $_GET['volta'] : 'index.php';

// Buscar dados da entrada
$sql_entrada = "SELECT ae.*, am.nome as material_nome, am.estoque_atual
                FROM almoxarifado_entradas ae
                JOIN almoxarifado_materiais am ON ae.material_id = am.id
                WHERE ae.id = ?";
$stmt_entrada = $pdo->prepare($sql_entrada);
$stmt_entrada->execute([$entrada_id]);
$entrada = $stmt_entrada->fetch(PDO::FETCH_ASSOC);

if(!$entrada){
    header('Location: ' . $volta);
    exit;
}

$pdo->beginTransaction();
try {
    // 1. Zerar estoque do material
    $sql_zerar_estoque = "UPDATE almoxarifado_materiais SET estoque_atual = 0 WHERE id = ?";
    $stmt_zerar_estoque = $pdo->prepare($sql_zerar_estoque);
    $stmt_zerar_estoque->execute([$entrada['material_id']]);
    
    // 2. Registrar movimentação de saída (zerando estoque)
    $sql_movimentacao = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) 
                         VALUES (?, 'saida', ?, ?, 0, ?, ?, ?)";
    $stmt_movimentacao = $pdo->prepare($sql_movimentacao);
    $stmt_movimentacao->execute([$entrada['material_id'], $entrada['estoque_atual'], $entrada['estoque_atual'], date('Y-m-d H:i:s'), $_SESSION['id'], $entrada_id]);
    
    // 3. Reverter saldo da nota fiscal
    $valor_entrada = $entrada['quantidade'] * $entrada['valor_unitario'];
    $sql_reverter_nota = "UPDATE notas_fiscais SET saldo = saldo + ? WHERE nota_numero = ?";
    $stmt_reverter_nota = $pdo->prepare($sql_reverter_nota);
    $stmt_reverter_nota->execute([$valor_entrada, $entrada['nota_fiscal']]);
    
    // 4. Excluir a entrada
    $sql_delete = "DELETE FROM almoxarifado_entradas WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$entrada_id]);
    
    $pdo->commit();
    $success = "Entrada excluída com sucesso! Estoque do material foi zerado e saldo da nota fiscal foi revertido.";
    header('Location: ' . $volta . '&success=' . urlencode($success));
    exit;
    
} catch (Exception $e) {
    $pdo->rollback();
    $error = "Erro ao excluir entrada: " . $e->getMessage();
    header('Location: ' . $volta . '&error=' . urlencode($error));
    exit;
}
?>
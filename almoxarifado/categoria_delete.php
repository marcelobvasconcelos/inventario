<?php
require_once '../config/db.php';

if($_SESSION["permissao"] != 'Administrador'){
    header("location: ../dashboard.php");
    exit;
}

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    
    // Verificar se a categoria está sendo usada
    $sql_check = "SELECT COUNT(*) FROM almoxarifado_materiais WHERE categoria LIKE ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute(["%{$id}%"]);
    
    if($stmt_check->fetchColumn() > 0){
        header("location: categoria_add.php?error=categoria_em_uso");
        exit;
    }
    
    // Excluir categoria
    $sql = "DELETE FROM almoxarifado_categorias WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if($stmt->execute([$id])){
        header("location: categoria_add.php?success=categoria_excluida");
    } else {
        header("location: categoria_add.php?error=erro_exclusao");
    }
} else {
    header("location: categoria_add.php");
}
?>
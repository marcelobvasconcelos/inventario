<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

// Apenas administradores podem acessar este endpoint
if(!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador'){
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';

$sql = "SELECT id, nome, patrimonio_novo FROM itens WHERE 1=1";
$params = [];
$types = "";

if ($location_id > 0) {
    $sql .= " AND local_id = ?";
    $params[] = $location_id;
    $types .= "i";
}

if (!empty($search_term)) {
    $sql .= " AND (nome LIKE ? OR patrimonio_novo LIKE ?)";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
    $types .= "ss";
}

$sql .= " ORDER BY nome ASC";

$items = [];
if($stmt = mysqli_prepare($link, $sql)){
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $items[] = $row;
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($link);

echo json_encode($items);
?>
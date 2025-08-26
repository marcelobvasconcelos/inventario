<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

// Apenas administradores podem acessar este endpoint
if(!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador'){
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// SQL base para buscar itens com informações relacionadas
$sql_base = "SELECT 
                i.id, 
                i.nome, 
                i.patrimonio_novo, 
                i.patrimonio_secundario, 
                i.estado, 
                i.status_confirmacao,
                l.id as local_id,
                l.nome AS local, 
                u.nome AS responsavel
            FROM itens i 
            JOIN locais l ON i.local_id = l.id 
            JOIN usuarios u ON i.responsavel_id = u.id";

$sql_count = "SELECT COUNT(*) FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id";

$where_clause = "";
$params = [];
$param_types = "";

// Consulta para contagem total
$sql_count_query = $sql_count . $where_clause;
if($stmt_count = mysqli_prepare($link, $sql_count_query)){
    if (!empty($params)) {
        $refs = [];
        foreach($params as $key => $value)
            $refs[$key] = &$params[$key];
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_count, $param_types], $refs));
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_items = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
} else {
    $total_items = 0;
}

$total_pages = ceil($total_items / $items_per_page);

// Consulta para os itens da página atual
$sql = $sql_base . $where_clause . " ORDER BY i.id DESC LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($link, $sql)){
    $bind_params = array_merge($params, [$items_per_page, $offset]);
    $bind_types = $param_types . "ii";
    $refs = [];
    foreach($bind_params as $key => $value)
        $refs[$key] = &$bind_params[$key];
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bind_types], $refs));
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while($row = mysqli_fetch_assoc($result)){
        $items[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $items = [];
}

mysqli_close($link);

echo json_encode([
    'items' => $items,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_items' => $total_items
]);
?>
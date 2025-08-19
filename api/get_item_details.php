<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID não informado']);
    exit;
}

$item_id = (int)$_GET['id'];

$sql = "SELECT i.*, l.nome AS local_nome, u.nome AS responsavel_nome
        FROM itens i
        LEFT JOIN locais l ON i.local_id = l.id
        LEFT JOIN usuarios u ON i.responsavel_id = u.id
        WHERE i.id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo json_encode(['error' => 'Item não encontrado']);
        exit;
    }

    // Normaliza datas em formatos compatíveis com inputs date
    if (!empty($row['data_entrada_aceitacao'])) {
        $row['data_entrada_aceitacao'] = date('Y-m-d', strtotime($row['data_entrada_aceitacao']));
    }
    if (!empty($row['data_emissao_empenho'])) {
        $row['data_emissao_empenho'] = date('Y-m-d', strtotime($row['data_emissao_empenho']));
    }

    echo json_encode($row);
    exit;
} else {
    echo json_encode(['error' => 'Erro ao preparar consulta: ' . mysqli_error($link)]);
    exit;
}

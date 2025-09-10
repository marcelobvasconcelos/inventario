<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 20; // Match PHP constant
$offset = ($pagina - 1) * $itens_por_pagina;

// Verificar permissões via sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$is_privileged_user = isset($_SESSION['permissao']) && in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);

try {
    // Construir query base
    $select_columns = "SELECT m.id, m.codigo, m.nome, m.categoria as categoria_nome, m.quantidade_maxima_requisicao";
    if ($is_privileged_user) {
        $select_columns .= ", m.estoque_atual, m.valor_unitario,
                             CASE
                                 WHEN m.estoque_atual <= 0 THEN 'sem_estoque'
                                 WHEN m.estoque_atual < 5 THEN 'estoque_baixo'
                                 ELSE 'estoque_normal'
                             END as situacao_estoque";
    }

    $sql_base = $select_columns . " FROM almoxarifado_materiais m";
    $sql_count_base = "SELECT COUNT(m.id) FROM almoxarifado_materiais m";

    $conditions = [];
    $params = [];

    if (!empty($query)) {
        $conditions[] = "(m.nome LIKE ? OR m.codigo LIKE ?)";
        $params[] = '%' . $query . '%';
        $params[] = '%' . $query . '%';
    }

    if (!empty($categoria)) {
        // Extrair descrição da categoria se for no formato "id - descricao"
        if (preg_match('/^\d+ - (.+)$/', $categoria, $matches)) {
            $categoria = $matches[1];
        }
        $conditions[] = "m.categoria = ?";
        $params[] = $categoria;
    }

    if ($is_privileged_user && !empty($status)) {
        switch($status) {
            case 'sem_estoque': $conditions[] = "m.estoque_atual <= 0"; break;
            case 'estoque_baixo': $conditions[] = "m.estoque_atual > 0 AND m.estoque_atual < 5"; break;
            case 'estoque_normal': $conditions[] = "m.estoque_atual >= 5"; break;
        }
    }

    $where_clause = "";
    if (!empty($conditions)) {
        $where_clause = " WHERE " . implode(" AND ", $conditions);
    }

    // Query de contagem
    $sql_count = $sql_count_base . $where_clause;
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_materiais = $stmt_count->fetchColumn();

    // Query de dados
    $sql = $sql_base . $where_clause . " ORDER BY m.nome ASC LIMIT " . $itens_por_pagina . " OFFSET " . $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_paginas = ceil($total_materiais / $itens_por_pagina);

    echo json_encode([
        'success' => true,
        'materiais' => $materiais,
        'total' => $total_materiais,
        'paginas' => $total_paginas,
        'pagina_atual' => $pagina
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
<?php
require_once '../config/db.php';
header('Content-Type: application/json');

// Verifica se o termo de busca foi enviado
$search_term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($search_term) < 2) {
    echo json_encode([]); // Retorna um array vazio se o termo for muito curto
    exit;
}

// --- Controle de Acesso ---
$is_privileged_user = isset($_SESSION['permissao']) && in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);

// Construção da consulta SQL com base no termo de busca e permissões
$select_columns = "m.id, m.nome, c.descricao as categoria_nome";
if ($is_privileged_user) {
    $select_columns .= ", m.qtd, m.valor_unit,
                       CASE
                           WHEN m.qtd <= 0 THEN 'sem_estoque'
                           WHEN m.qtd < 5 THEN 'estoque_baixo'
                           ELSE 'estoque_normal'
                       END as situacao_estoque";
}

$sql = "SELECT " . $select_columns . " FROM materiais m LEFT JOIN categorias c ON m.categoria_id = c.id WHERE m.nome LIKE ? ORDER BY m.nome ASC LIMIT 20";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $search_term . '%']);
    $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata o valor unitário e o status do estoque para o frontend
    foreach ($materiais as &$material) {
        if ($is_privileged_user) {
            $material['valor_unit'] = number_format($material['valor_unit'], 2, ',', '.');
            switch ($material['situacao_estoque']) {
                case 'sem_estoque':
                    $material['status_badge'] = '<span class="badge badge-danger">Sem estoque</span>';
                    break;
                case 'estoque_baixo':
                    $material['status_badge'] = '<span class="badge badge-warning">Estoque baixo</span>';
                    break;
                case 'estoque_normal':
                    $material['status_badge'] = '<span class="badge badge-success">Normal</span>';
                    break;
            }
        }
    }

    echo json_encode($materiais);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar materiais: ' . $e->getMessage()]);
}
?>

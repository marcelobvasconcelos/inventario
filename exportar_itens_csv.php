<?php
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['id']) || $_SESSION['permissao'] != 'Administrador') {
    die("Acesso negado.");
}

require 'config/db.php';

// Receber dados do POST (filtros de pesquisa)
$search_by = $_POST['search_by'] ?? '';
$search_query = $_POST['search_query'] ?? '';

// Montar a consulta SQL com base nos filtros (sem paginação)
$sql_base = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.nome AS local, u.nome AS responsavel, i.estado FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id";
$where_clause = "";
$params = [];

if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    $field_map = [
        'id' => 'i.id',
        'patrimonio_novo' => 'i.patrimonio_novo',
        'patrimonio_secundario' => 'i.patrimonio_secundario',
        'local' => 'l.nome',
        'responsavel' => 'u.nome'
    ];

    if (array_key_exists($search_by, $field_map)) {
        $where_clause = " WHERE " . $field_map[$search_by] . " LIKE ?";
        $params[] = $search_term;
    } else {
        $where_clause = " WHERE i.nome LIKE ?"; // Fallback para nome
        $params[] = $search_term;
    }
}

$sql = $sql_base . $where_clause . " ORDER BY i.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Definir cabeçalhos para download de CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=itens_inventario.csv');

// Abrir o arquivo de saída (php://output envia diretamente para o navegador)
$output = fopen('php://output', 'w');

// Escrever o cabeçalho do CSV
fputcsv($output, ['ID', 'Nome', 'Patrimônio', 'Patrimônio Secundário', 'Local', 'Responsável', 'Estado']);

// Escrever os dados dos itens
foreach ($itens as $item) {
    fputcsv($output, [
        $item['id'],
        $item['nome'],
        $item['patrimonio_novo'],
        $item['patrimonio_secundario'],
        $item['local'],
        $item['responsavel'],
        $item['estado']
    ]);
}

// Fechar o arquivo
fclose($output);
exit;
?>
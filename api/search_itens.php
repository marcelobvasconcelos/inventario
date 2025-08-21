<?php
require_once '../config/db.php';
header('Content-Type: application/json');

// Verifica se o termo de busca foi enviado
if (!isset($_GET['term']) || empty($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$search_term = $_GET['term'];
$search_term_like = '%' . $search_term . '%';

// Prepara a consulta SQL para buscar itens
$sql = "SELECT id, nome, patrimonio_novo FROM itens WHERE nome LIKE ? OR patrimonio_novo LIKE ? ORDER BY nome ASC LIMIT 10";
$stmt = mysqli_prepare($link, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $search_term_like, $search_term_like);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'patrimonio_novo' => $row['patrimonio_novo']
            ];
        }
        
        echo json_encode($items);
    } else {
        echo json_encode(['error' => 'Erro ao executar a consulta.']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['error' => 'Erro ao preparar a consulta.']);
}

mysqli_close($link);
?>
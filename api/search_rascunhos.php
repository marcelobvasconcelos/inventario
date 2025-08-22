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

// Prepara a consulta SQL para buscar rascunhos
$sql = "SELECT id, nome FROM rascunhos_itens WHERE nome LIKE ? ORDER BY nome ASC LIMIT 10";
$stmt = mysqli_prepare($link, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $search_term_like);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $rascunhos = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $rascunhos[] = [
                'id' => $row['id'],
                'nome' => $row['nome']
            ];
        }
        
        echo json_encode($rascunhos);
    } else {
        echo json_encode(['error' => 'Erro ao executar a consulta.']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['error' => 'Erro ao preparar a consulta.']);
}

mysqli_close($link);
?>
<?php
// API para buscar notas fiscais de empenhos abertos com base em um termo de pesquisa
require_once '../config/db.php';

// Verificar se é uma requisição AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Verificar permissões - apenas administradores podem acessar
if (!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador') {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['term'])) {
    $term = trim($_GET['term']);
    
    if (strlen($term) >= 1) {
        try {
            // Buscar notas fiscais de empenhos abertos que contenham o termo pesquisado
            $sql = "SELECT nf.nota_numero, nf.nota_valor, ei.fornecedor
                    FROM notas_fiscais nf
                    JOIN empenhos_insumos ei ON nf.empenho_numero = ei.numero
                    WHERE ei.status = 'Aberto'
                    AND nf.nota_numero LIKE ?
                    ORDER BY nf.nota_numero ASC
                    LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["%$term%"]);
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($notas);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Erro ao buscar notas fiscais']);
        }
    } else {
        // Se o termo for muito curto, retornar todas as notas fiscais de empenhos abertos
        try {
            $sql = "SELECT nf.nota_numero, nf.nota_valor, ei.fornecedor
                    FROM notas_fiscais nf
                    JOIN empenhos_insumos ei ON nf.empenho_numero = ei.numero
                    WHERE ei.status = 'Aberto'
                    ORDER BY nf.nota_numero ASC
                    LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($notas);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Erro ao buscar notas fiscais']);
        }
    }
} else {
    echo json_encode([]);
}
?>
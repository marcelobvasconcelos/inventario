<?php
// verificar_requisicoes.php - Script para verificar as requisições no almoxarifado
require_once 'config/db.php';

echo "Verificando requisições no almoxarifado...\n\n";

try {
    // Buscar todas as requisições com detalhes
    $stmt = $pdo->prepare("
        SELECT 
            r.id, 
            r.usuario_id, 
            r.data_requisicao, 
            r.status, 
            r.justificativa,
            u.nome as usuario_nome
        FROM almoxarifado_requisicoes r
        JOIN usuarios u ON r.usuario_id = u.id
        ORDER BY r.data_requisicao DESC
    ");
    $stmt->execute();
    $requisicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($requisicoes) > 0) {
        echo "Requisições no almoxarifado:\n";
        echo str_repeat("=", 120) . "\n";
        
        foreach ($requisicoes as $requisicao) {
            echo "Requisição #" . $requisicao['id'] . " - " . $requisicao['usuario_nome'] . "\n";
            echo "  Data: " . $requisicao['data_requisicao'] . "\n";
            echo "  Status: " . strtoupper($requisicao['status']) . "\n";
            echo "  Justificativa: " . $requisicao['justificativa'] . "\n";
            
            // Buscar os itens da requisição
            $stmt_itens = $pdo->prepare("
                SELECT 
                    i.quantidade_solicitada,
                    p.nome as produto_nome,
                    p.unidade_medida as produto_unidade
                FROM almoxarifado_requisicoes_itens i
                JOIN almoxarifado_produtos p ON i.produto_id = p.id
                WHERE i.requisicao_id = ?
            ");
            $stmt_itens->execute([$requisicao['id']]);
            $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
            
            echo "  Itens solicitados:\n";
            foreach ($itens as $item) {
                echo "    - " . $item['quantidade_solicitada'] . " " . $item['produto_unidade'] . " de " . $item['produto_nome'] . "\n";
            }
            
            echo str_repeat("-", 60) . "\n";
        }
        
        echo "Total de requisições: " . count($requisicoes) . "\n";
    } else {
        echo "Nenhuma requisição encontrada no almoxarifado.\n";
    }
    
} catch (Exception $e) {
    echo "Erro ao verificar requisições: " . $e->getMessage() . "\n";
    exit(1);
}
?>
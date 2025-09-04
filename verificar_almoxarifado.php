<?php
// verificar_almoxarifado.php - Script para verificar os produtos no almoxarifado
require_once 'config/db.php';

echo "Verificando produtos no almoxarifado...\n\n";

try {
    // Buscar todos os produtos
    $stmt = $pdo->prepare("SELECT * FROM almoxarifado_produtos ORDER BY nome");
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($produtos) > 0) {
        echo "Produtos no almoxarifado:\n";
        echo str_repeat("-", 100) . "\n";
        printf("%-5s %-40s %-15s %-10s %-10s %-10s\n", "ID", "Nome", "Unidade", "Estoque", "Mínimo", "Status");
        echo str_repeat("-", 100) . "\n";
        
        foreach ($produtos as $produto) {
            // Determinar status
            $status = ($produto['estoque_atual'] <= $produto['estoque_minimo']) ? "BAIXO" : "NORMAL";
            
            printf("%-5s %-40s %-15s %-10s %-10s %-10s\n", 
                $produto['id'], 
                substr($produto['nome'], 0, 40), 
                $produto['unidade_medida'], 
                $produto['estoque_atual'], 
                $produto['estoque_minimo'], 
                $status
            );
        }
        
        echo str_repeat("-", 100) . "\n";
        echo "Total de produtos: " . count($produtos) . "\n";
    } else {
        echo "Nenhum produto encontrado no almoxarifado.\n";
    }
    
} catch (Exception $e) {
    echo "Erro ao verificar almoxarifado: " . $e->getMessage() . "\n";
    exit(1);
}
?>
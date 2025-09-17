<?php
// Script para corrigir o cálculo do saldo dos empenhos
require_once 'config/db.php';

echo "<h2>Correção do Saldo dos Empenhos</h2>\n";
echo "<p>Iniciando correção do cálculo do saldo dos empenhos...</p>\n";

try {
    // Primeiro, vamos verificar a situação atual
    echo "<h3>1. Situação Atual dos Empenhos</h3>\n";
    
    $sql_verificacao = "SELECT 
        ei.numero as empenho,
        ei.fornecedor,
        ei.valor as valor_empenho,
        COALESCE(SUM(nf.nota_valor), 0) as total_notas_fiscais,
        ei.saldo as saldo_atual,
        (ei.valor - COALESCE(SUM(nf.nota_valor), 0)) as saldo_correto,
        COUNT(nf.id) as quantidade_notas
    FROM empenhos_insumos ei
    LEFT JOIN notas_fiscais nf ON ei.numero = nf.empenho_numero
    GROUP BY ei.numero, ei.fornecedor, ei.valor, ei.saldo
    ORDER BY ei.numero";
    
    $stmt_verificacao = $pdo->prepare($sql_verificacao);
    $stmt_verificacao->execute();
    $empenhos_antes = $stmt_verificacao->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Empenho</th><th>Fornecedor</th><th>Valor Empenho</th><th>Total Notas</th><th>Saldo Atual</th><th>Saldo Correto</th><th>Qtd Notas</th><th>Status</th></tr>\n";
    
    $empenhos_incorretos = 0;
    foreach($empenhos_antes as $empenho) {
        $status = ($empenho['saldo_atual'] == $empenho['saldo_correto']) ? 'OK' : 'INCORRETO';
        if($status == 'INCORRETO') $empenhos_incorretos++;
        
        $cor = ($status == 'OK') ? '#d4edda' : '#f8d7da';
        echo "<tr style='background-color: {$cor};'>";
        echo "<td>{$empenho['empenho']}</td>";
        echo "<td>{$empenho['fornecedor']}</td>";
        echo "<td>R$ " . number_format($empenho['valor_empenho'], 2, ',', '.') . "</td>";
        echo "<td>R$ " . number_format($empenho['total_notas_fiscais'], 2, ',', '.') . "</td>";
        echo "<td>R$ " . number_format($empenho['saldo_atual'], 2, ',', '.') . "</td>";
        echo "<td>R$ " . number_format($empenho['saldo_correto'], 2, ',', '.') . "</td>";
        echo "<td>{$empenho['quantidade_notas']}</td>";
        echo "<td><strong>{$status}</strong></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<p><strong>Resumo:</strong> " . count($empenhos_antes) . " empenhos encontrados, {$empenhos_incorretos} com saldo incorreto.</p>\n";
    
    if($empenhos_incorretos > 0) {
        echo "<h3>2. Corrigindo Saldos dos Empenhos</h3>\n";
        
        // Atualizar o saldo de todos os empenhos
        $sql_correcao = "UPDATE empenhos_insumos ei
                        SET saldo = (
                            ei.valor - COALESCE(
                                (SELECT SUM(nf.nota_valor) 
                                 FROM notas_fiscais nf 
                                 WHERE nf.empenho_numero = ei.numero), 
                                0
                            )
                        )";
        
        $stmt_correcao = $pdo->prepare($sql_correcao);
        $resultado = $stmt_correcao->execute();
        
        if($resultado) {
            $linhas_afetadas = $stmt_correcao->rowCount();
            echo "<p style='color: green;'><strong>✓ Correção executada com sucesso!</strong> {$linhas_afetadas} empenhos atualizados.</p>\n";
            
            // Verificar a situação após a correção
            echo "<h3>3. Situação Após a Correção</h3>\n";
            
            $stmt_verificacao->execute();
            $empenhos_depois = $stmt_verificacao->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>Empenho</th><th>Fornecedor</th><th>Valor Empenho</th><th>Total Notas</th><th>Saldo Atual</th><th>Qtd Notas</th><th>Status</th></tr>\n";
            
            $empenhos_ainda_incorretos = 0;
            foreach($empenhos_depois as $empenho) {
                $saldo_correto = $empenho['valor_empenho'] - $empenho['total_notas_fiscais'];
                $status = (abs($empenho['saldo_atual'] - $saldo_correto) < 0.01) ? 'OK' : 'INCORRETO';
                if($status == 'INCORRETO') $empenhos_ainda_incorretos++;
                
                $cor = ($status == 'OK') ? '#d4edda' : '#f8d7da';
                echo "<tr style='background-color: {$cor};'>";
                echo "<td>{$empenho['empenho']}</td>";
                echo "<td>{$empenho['fornecedor']}</td>";
                echo "<td>R$ " . number_format($empenho['valor_empenho'], 2, ',', '.') . "</td>";
                echo "<td>R$ " . number_format($empenho['total_notas_fiscais'], 2, ',', '.') . "</td>";
                echo "<td>R$ " . number_format($empenho['saldo_atual'], 2, ',', '.') . "</td>";
                echo "<td>{$empenho['quantidade_notas']}</td>";
                echo "<td><strong>{$status}</strong></td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
            
            if($empenhos_ainda_incorretos == 0) {
                echo "<p style='color: green; font-size: 18px;'><strong>🎉 SUCESSO! Todos os saldos dos empenhos foram corrigidos!</strong></p>\n";
            } else {
                echo "<p style='color: red;'><strong>⚠️ ATENÇÃO:</strong> Ainda existem {$empenhos_ainda_incorretos} empenhos com saldo incorreto. Verifique manualmente.</p>\n";
            }
            
        } else {
            echo "<p style='color: red;'><strong>✗ Erro ao executar a correção!</strong></p>\n";
        }
    } else {
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Todos os saldos dos empenhos já estão corretos!</strong></p>\n";
    }
    
    // Verificar empenhos com saldo negativo
    echo "<h3>4. Verificação de Empenhos com Saldo Negativo</h3>\n";
    
    $sql_negativos = "SELECT 
        ei.numero as empenho,
        ei.fornecedor,
        ei.valor as valor_empenho,
        COALESCE(SUM(nf.nota_valor), 0) as total_notas_fiscais,
        ei.saldo as saldo_atual
    FROM empenhos_insumos ei
    LEFT JOIN notas_fiscais nf ON ei.numero = nf.empenho_numero
    GROUP BY ei.numero, ei.fornecedor, ei.valor, ei.saldo
    HAVING ei.saldo < 0
    ORDER BY ei.saldo ASC";
    
    $stmt_negativos = $pdo->prepare($sql_negativos);
    $stmt_negativos->execute();
    $empenhos_negativos = $stmt_negativos->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($empenhos_negativos) > 0) {
        echo "<p style='color: orange;'><strong>⚠️ ATENÇÃO:</strong> Encontrados " . count($empenhos_negativos) . " empenhos com saldo negativo (valor das notas fiscais maior que o valor do empenho):</p>\n";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Empenho</th><th>Fornecedor</th><th>Valor Empenho</th><th>Total Notas</th><th>Saldo</th><th>Diferença</th></tr>\n";
        
        foreach($empenhos_negativos as $empenho) {
            $diferenca = $empenho['total_notas_fiscais'] - $empenho['valor_empenho'];
            echo "<tr style='background-color: #fff3cd;'>";
            echo "<td>{$empenho['empenho']}</td>";
            echo "<td>{$empenho['fornecedor']}</td>";
            echo "<td>R$ " . number_format($empenho['valor_empenho'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($empenho['total_notas_fiscais'], 2, ',', '.') . "</td>";
            echo "<td style='color: red;'>R$ " . number_format($empenho['saldo_atual'], 2, ',', '.') . "</td>";
            echo "<td style='color: red;'>R$ " . number_format($diferenca, 2, ',', '.') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<p><em>Estes empenhos precisam de revisão manual, pois o valor das notas fiscais excede o valor do empenho.</em></p>\n";
    } else {
        echo "<p style='color: green;'><strong>✓ Nenhum empenho com saldo negativo encontrado!</strong></p>\n";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Correção finalizada em " . date('d/m/Y H:i:s') . "</em></p>\n";
?>
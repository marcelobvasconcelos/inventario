<?php
// Script para corrigir o erro de chaves no arquivo notificacoes_usuario.php

$arquivo = 'notificacoes_usuario.php';
if (file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    $linhas = explode("\n", $conteudo);
    
    echo "Corrigindo erro de chaves no arquivo...\n";
    
    // Verificar se a última chave está errada
    $ultima_linha = trim(end($linhas));
    if ($ultima_linha === '}') {
        echo "Removendo chave extra no final do arquivo...\n";
        array_pop($linhas); // Remover a chave extra
        
        // Recriar o conteúdo corrigido
        $conteudo_corrigido = implode("\n", $linhas);
        
        // Salvar o arquivo corrigido
        file_put_contents($arquivo, $conteudo_corrigido);
        echo "Arquivo corrigido com sucesso!\n";
    } else {
        echo "A última linha não é uma chave. Verificando outras possibilidades...\n";
        
        // Procurar por chaves extras em outras partes do código
        $chaves_abertas = 0;
        $chaves_fechadas = 0;
        
        foreach ($linhas as $numero => $linha) {
            $chaves_abertas += substr_count($linha, '{');
            $chaves_fechadas += substr_count($linha, '}');
            
            if ($chaves_fechadas > $chaves_abertas) {
                echo "Possível chave extra na linha " . ($numero + 1) . ": $linha\n";
            }
        }
    }
    
    // Verificar novamente
    $conteudo_atualizado = file_get_contents($arquivo);
    $chaves_abertas = substr_count($conteudo_atualizado, '{');
    $chaves_fechadas = substr_count($conteudo_atualizado, '}');
    
    echo "\nApós correção:\n";
    echo "Chaves abertas: $chaves_abertas\n";
    echo "Chaves fechadas: $chaves_fechadas\n";
    echo "Diferença: " . ($chaves_abertas - $chaves_fechadas) . "\n";
    
    if ($chaves_abertas == $chaves_fechadas) {
        echo "✓ Arquivo corrigido com sucesso!\n";
    } else {
        echo "✗ Ainda há problemas com as chaves.\n";
    }
}
?>
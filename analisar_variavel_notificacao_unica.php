<?php
// Script para verificar exatamente como a variável $notificacao_unica_id está sendo usada

echo "=== ANÁLISE DA VARIÁVEL \$notificacao_unica_id ===\n\n";

$arquivo_usuario = 'notificacoes_usuario.php';
if (file_exists($arquivo_usuario)) {
    $conteudo = file_get_contents($arquivo_usuario);
    
    echo "--- DEFINIÇÃO DA VARIÁVEL ---\n";
    
    // Procurar a definição exata
    if (preg_match('/\\$notificacao_unica_id\\s*=\\s*isset\\(\\$_GET\\[\'notif_id\'\\]\\)/', $conteudo)) {
        echo "✅ \$notificacao_unica_id definida como isset(\$_GET['notif_id'])\n";
    } else {
        echo "ℹ️ Procurando definição da variável...\n";
        
        // Procurar todas as definições
        $padroes_definicao = [
            '\\$notificacao_unica_id\\s*=',
            '\\$_GET\\[\'notif_id\'\\]',
            'notif_id'
        ];
        
        foreach ($padroes_definicao as $padrao) {
            if (preg_match_all("/$padrao/", $conteudo, $matches)) {
                echo "Encontrado padrão: $padrao (" . count($matches[0]) . " ocorrências)\n";
            }
        }
    }
    
    echo "\n--- CONDIÇÕES DE EXIBIÇÃO ---\n";
    
    // Procurar condições que usam a variável
    $padroes_condicoes = [
        'if \\(\\$notificacao_unica_id > 0\\)',
        'if \\(\\$notificacao_unica_id != 0\\)',
        'if \\(!\\$notificacao_unica_id\\)',
        'if \\(isset\\(\\$_GET\\[\'notif_id\'\\]\\)\\)',
        '\\$notificacao_unica_id'
    ];
    
    foreach ($padroes_condicoes as $padrao) {
        if (preg_match_all("/$padrao/", $conteudo, $matches)) {
            echo "Condição encontrada: $padrao (" . count($matches[0]) . " ocorrências)\n";
            
            // Mostrar contexto
            $posicoes = [];
            $pos = 0;
            while (($pos = strpos($conteudo, $matches[0][0], $pos)) !== false) {
                $posicoes[] = $pos;
                $pos++;
            }
            
            foreach ($posicoes as $i => $pos) {
                if ($i < 3) { // Limitar a 3 exemplos
                    $contexto = substr($conteudo, max(0, $pos - 100), 200);
                    $linha = substr_count(substr($conteudo, 0, $pos), "\n") + 1;
                    echo "  Linha $linha: " . str_replace(["\n", "\r", "\t"], " ", trim($contexto)) . "\n";
                }
            }
            
            if (count($posicoes) > 3) {
                echo "  ... (mais " . (count($posicoes) - 3) . " ocorrências)\n";
            }
        }
    }
    
    echo "\n--- ESTRUTURA DE EXIBIÇÃO ---\n";
    
    // Verificar como as notificações são exibidas
    if (strpos($conteudo, 'foreach ($notificacoes as $notificacao)') !== false) {
        echo "✅ Loop principal de exibição encontrado\n";
        
        // Verificar contexto do loop
        $pos_loop = strpos($conteudo, 'foreach ($notificacoes as $notificacao)');
        $linha_loop = substr_count(substr($conteudo, 0, $pos_loop), "\n") + 1;
        echo "  Loop na linha: $linha_loop\n";
        
        // Verificar se está dentro de uma condição
        $conteudo_antes = substr($conteudo, 0, $pos_loop);
        $linhas_antes = explode("\n", $conteudo_antes);
        
        // Procurar condições que envolvem o loop
        $condicoes_proximas = [];
        for ($i = max(0, count($linhas_antes) - 20); $i < count($linhas_antes); $i++) {
            $linha = $linhas_antes[$i];
            if (preg_match('/if\\s*\\(.*\\)/', $linha) || 
                strpos($linha, 'if (') !== false ||
                strpos($linha, 'if(') !== false) {
                $condicoes_proximas[] = ['linha' => $i + 1, 'conteudo' => trim($linha)];
            }
        }
        
        if (count($condicoes_proximas) > 0) {
            echo "  Condições próximas ao loop:\n";
            foreach ($condicoes_proximas as $cond) {
                echo "    Linha " . $cond['linha'] . ": " . $cond['conteudo'] . "\n";
            }
        }
    }
    
    echo "\n--- VERIFICANDO VALOR PADRÃO DA VARIÁVEL ---\n";
    
    // Verificar se há valor padrão para a variável
    if (strpos($conteudo, '$notificacao_unica_id = 0') !== false) {
        echo "✅ Valor padrão de \$notificacao_unica_id definido como 0\n";
    } else {
        echo "ℹ️ Verificando valor padrão...\n";
        
        // Procurar definições com valores padrão
        if (preg_match('/\\$notificacao_unica_id\\s*=\\s*(\\d+)/', $conteudo, $matches)) {
            echo "Valor padrão: " . $matches[1] . "\n";
        } else {
            echo "Sem valor padrão definido\n";
        }
    }
    
    echo "\n--- VERIFICANDO SE HÁ LIMITAÇÃO NO PROCESSAMENTO ---\n";
    
    // Procurar por break, continue, exit, die, etc. que possam limitar o processamento
    $comandos_limitadores = ['break', 'continue', 'exit', 'die', 'return'];
    foreach ($comandos_limitadores as $comando) {
        if (strpos($conteudo, $comando) !== false) {
            $ocorrencias = substr_count($conteudo, $comando);
            echo "Comando '$comando' encontrado ($ocorrencias ocorrências)\n";
        }
    }
    
    echo "\n--- VERIFICANDO ESTRUTURA CONDICIONAL ---\n";
    
    // Verificar a estrutura condicional completa
    if (strpos($conteudo, 'if ($notificacao_unica_id > 0)') !== false) {
        echo "❌ ENCONTRADO: Condição que limita a exibição a uma única notificação\n";
        
        // Verificar contexto completo
        $pos_condicao = strpos($conteudo, 'if ($notificacao_unica_id > 0)');
        $contexto = substr($conteudo, max(0, $pos_condicao - 200), 400);
        echo "Contexto:\n" . str_replace(["\n", "\r", "\t"], " ", $contexto) . "\n";
    }
    
    echo "\n--- ANALISANDO FLUXO DE EXECUÇÃO ---\n";
    
    // Procurar por estruturas if/else que possam estar afetando a exibição
    $padroes_fluxo = [
        'if \\(\\$notificacao_unica_id > 0\\).*?else',
        'if \\(isset\\(\\$_GET\\[\'notif_id\'\\]\\).*?else',
        'if \\(!isset\\(\\$_GET\\[\'notif_id\'\\]\\).*?else'
    ];
    
    foreach ($padroes_fluxo as $padrao) {
        if (preg_match("/$padrao/s", $conteudo, $matches)) {
            echo "Estrutura if/else encontrada:\n";
            echo str_replace(["\n", "\r", "\t"], " ", substr($matches[0], 0, 200)) . "...\n";
        }
    }
    
    echo "\n=== HIPÓTESE IDENTIFICADA ===\n";
    echo "O problema provavelmente está na condição:\n";
    echo "  if (\$notificacao_unica_id > 0) {\n";
    echo "    // Exibe apenas uma notificação\n";
    echo "  } else {\n";
    echo "    // Exibe todas as notificações\n";
    echo "  }\n";
    echo "\nMas se \$notificacao_unica_id está sendo definida incorretamente,\n";
    echo "isso pode estar causando a exibição limitada.\n";
    
} else {
    echo "❌ Arquivo notificacoes_usuario.php NÃO encontrado\n";
}

echo "\n=== FIM DA ANÁLISE ===\n";

?>
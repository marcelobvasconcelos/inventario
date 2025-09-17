<?php
// Script para analisar o conteúdo completo do arquivo de notificações do usuário

echo "=== ANÁLISE COMPLETA DO ARQUIVO notificacoes_usuario.php ===\n\n";

$arquivo = 'notificacoes_usuario.php';
if (file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    $linhas = explode("\n", $conteudo);
    
    echo "Total de linhas: " . count($linhas) . "\n\n";
    
    // Procurar por seções específicas
    echo "1. Seções encontradas:\n";
    
    // Procurar por declaração de variável $notificacoes
    $linha_notificacoes = 0;
    foreach ($linhas as $num => $linha) {
        if (strpos($linha, '$notificacoes = [];') !== false) {
            $linha_notificacoes = $num + 1;
            echo "   - Variável \$notificacoes declarada na linha $linha_notificacoes\n";
            break;
        }
    }
    
    // Procurar por consultas SQL
    $consultas_sql = [];
    foreach ($linhas as $num => $linha) {
        if (preg_match('/\\$sql\\s*=\\s*["\'].*SELECT/i', $linha)) {
            $consultas_sql[] = $num + 1;
        }
    }
    
    if (count($consultas_sql) > 0) {
        echo "   - Consultas SQL encontradas nas linhas: " . implode(', ', $consultas_sql) . "\n";
    } else {
        echo "   - NENHUMA consulta SQL encontrada!\n";
    }
    
    // Procurar por PDO ou mysqli
    $pdo_usado = false;
    $mysqli_usado = false;
    foreach ($linhas as $linha) {
        if (strpos($linha, '$pdo') !== false) {
            $pdo_usado = true;
        }
        if (strpos($linha, '$link') !== false || strpos($linha, 'mysqli') !== false) {
            $mysqli_usado = true;
        }
    }
    
    echo "   - PDO usado: " . ($pdo_usado ? "Sim" : "Não") . "\n";
    echo "   - MySQLi usado: " . ($mysqli_usado ? "Sim" : "Não") . "\n";
    
    echo "\n2. Conteúdo ao redor da declaração de \$notificacoes:\n";
    $inicio = max(0, $linha_notificacoes - 10);
    $fim = min(count($linhas), $linha_notificacoes + 10);
    
    for ($i = $inicio; $i < $fim; $i++) {
        $indicador = ($i + 1 == $linha_notificacoes) ? ">>> " : "    ";
        echo $indicador . ($i + 1) . ": " . trim($linhas[$i]) . "\n";
    }
    
    echo "\n3. Últimas linhas do arquivo:\n";
    $ultimas_linhas = array_slice($linhas, -20);
    foreach ($ultimas_linhas as $num => $linha) {
        $indice_real = count($linhas) - 20 + $num + 1;
        echo "    " . $indice_real . ": " . trim($linha) . "\n";
    }
    
    echo "\n4. Verificando se há comentários sobre correções:\n";
    if (strpos($conteudo, 'CORREÇÃO APLICADA') !== false) {
        echo "   - Foram encontradas correções aplicadas no arquivo\n";
        
        // Contar quantas correções foram aplicadas
        preg_match_all('/CORREÇÃO APLICADA/', $conteudo, $matches);
        echo "   - Total de correções aplicadas: " . count($matches[0]) . "\n";
    } else {
        echo "   - Nenhuma correção encontrada\n";
    }
    
} else {
    echo "Arquivo não encontrado!\n";
}

echo "\n=== FIM DA ANÁLISE ===\n";
?>
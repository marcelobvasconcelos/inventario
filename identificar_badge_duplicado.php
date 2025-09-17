<?php
// Script para identificar exatamente qual é o badge duplicado

$arquivo = 'notificacoes_usuario.php';
if (file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    $linhas = explode("\n", $conteudo);
    
    echo "=== IDENTIFICANDO BADGE JUNTO COM A DATA ===\n\n";
    
    // Procurar pela linha onde está o badge junto com a data (linha ~370)
    for ($i = 360; $i <= 390; $i++) {
        if (isset($linhas[$i - 1]) && (strpos($linhas[$i - 1], 'badge') !== false || strpos($linhas[$i - 1], 'date(') !== false)) {
            echo "Linha $i: " . trim($linhas[$i - 1]) . "\n";
        }
    }
    
    echo "\n=== VERIFICANDO SE HÁ BADGE DUPLICADO ===\n";
    // Verificar se há dois badges exibindo o mesmo status
    
    // Primeiro badge (na linha ~370)
    echo "Primeiro badge (junto com a data):\n";
    for ($i = 365; $i <= 385; $i++) {
        if (isset($linhas[$i - 1]) && strpos($linhas[$i - 1], 'badge') !== false) {
            echo "  Linha $i: " . trim($linhas[$i - 1]) . "\n";
        }
    }
    
    // Segundo badge (na linha ~461)
    echo "\nSegundo badge (na lista de detalhes):\n";
    for ($i = 455; $i <= 475; $i++) {
        if (isset($linhas[$i - 1]) && strpos($linhas[$i - 1], 'badge') !== false) {
            echo "  Linha $i: " . trim($linhas[$i - 1]) . "\n";
        }
    }
    
    echo "\n=== SOLUÇÃO ===\n";
    echo "O problema é que há dois badges exibindo o mesmo status de confirmação:\n";
    echo "1. Um badge junto com a data (na linha ~370)\n";
    echo "2. Um badge na lista de detalhes do item (na linha ~461)\n\n";
    echo "Vamos remover o segundo badge (linhas 461-469) mantendo apenas o primeiro.\n";
}
?>
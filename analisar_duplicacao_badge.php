<?php
// Script para identificar e corrigir a duplicação na badge do status de confirmação

$arquivo = 'notificacoes_usuario.php';
if (file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    $linhas = explode("\n", $conteudo);
    
    echo "=== ANÁLISE DA DUPLICAÇÃO NA BADGE ===\n\n";
    
    // Procurar pela linha onde o status é exibido na badge
    $badge_lines = [];
    for ($i = 1; $i <= count($linhas); $i++) {
        if (strpos($linhas[$i - 1], 'badge') !== false && strpos($linhas[$i - 1], 'status') !== false) {
            $badge_lines[] = ['linha' => $i, 'conteudo' => $linhas[$i - 1]];
        }
    }
    
    echo "Linhas com badges relacionadas a status:\n";
    foreach ($badge_lines as $badge) {
        echo "  Linha {$badge['linha']}: " . trim($badge['conteudo']) . "\n";
    }
    
    echo "\n=== VERIFICANDO ÁREA DA LINHA 461 (STATUS CONFIRMAÇÃO) ===\n";
    // Verificar contexto da linha 461 onde está o status de confirmação
    $linha_status = 461;
    $inicio = max(1, $linha_status - 5);
    $fim = min(count($linhas), $linha_status + 15);
    
    for ($i = $inicio; $i <= $fim; $i++) {
        $indicador = ($i == $linha_status) ? ">>> " : "    ";
        echo $indicador . $i . ": " . trim($linhas[$i - 1]) . "\n";
    }
    
    echo "\n=== IDENTIFICANDO DUPLICAÇÃO ===\n";
    // Verificar se há duas exibições do mesmo status
    $conteudo_status = $linhas[$linha_status - 1]; // Linha 461
    echo "Conteúdo da linha 461: " . trim($conteudo_status) . "\n";
    
    // Verificar se há outro lugar onde o status é exibido
    for ($i = max(1, $linha_status - 10); $i <= min(count($linhas), $linha_status + 10); $i++) {
        if (strpos($linhas[$i - 1], '$item[\'status_confirmacao\']') !== false && $i != $linha_status) {
            echo "Outra menção ao status_confirmacao na linha $i: " . trim($linhas[$i - 1]) . "\n";
        }
    }
    
    echo "\n=== SOLUÇÃO PROPOSTA ===\n";
    echo "Remover a linha 461 que exibe o status de confirmação duplicado,\n";
    echo "mantendo apenas o badge que está junto com a data.\n";
}
?>
<?php
// Script para corrigir especificamente a duplicação do status de confirmação

echo "=== CORREÇÃO DA DUPLICAÇÃO DO STATUS DE CONFIRMAÇÃO ===\n\n";

$arquivo_usuario = 'notificacoes_usuario.php';
if (file_exists($arquivo_usuario)) {
    echo "Corrigindo $arquivo_usuario...\n";
    
    $conteudo = file_get_contents($arquivo_usuario);
    
    // Procurar pela parte que exibe o status de confirmação duplicado
    // Vamos identificar onde o status está sendo exibido duas vezes
    
    // Primeiro, vamos verificar se há duas menções ao status na mesma área
    $padroes_status = [
        'Status Confirmação:',
        'status_confirmacao',
        'item_status',
        'notif_status'
    ];
    
    foreach ($padroes_status as $padrao) {
        $ocorrencias = substr_count($conteudo, $padrao);
        if ($ocorrencias > 1) {
            echo "  ⚠️  Padrão '$padrao' encontrado $ocorrencias vezes\n";
        }
    }
    
    // Vamos procurar especificamente pela estrutura problemática
    // Procurar por <li><strong>Status Confirmação:</strong>
    if (strpos($conteudo, '<li><strong>Status Confirmação:</strong>') !== false) {
        echo "  ❌ Encontrada estrutura de status duplicado\n";
        
        // Verificar contexto
        $pos = strpos($conteudo, '<li><strong>Status Confirmação:</strong>');
        $contexto_antes = substr($conteudo, max(0, $pos - 100), 100);
        $contexto_depois = substr($conteudo, $pos, 200);
        
        echo "  Contexto antes: " . str_replace(["\n", "\r", "\t"], " ", trim($contexto_antes)) . "\n";
        echo "  Contexto depois: " . str_replace(["\n", "\r", "\t"], " ", trim($contexto_depois)) . "\n";
        
        // Remover a linha duplicada
        $conteudo = str_replace('<li><strong>Status Confirmação:</strong>', '', $conteudo);
        echo "  ✅ Linha duplicada removida\n";
    }
    
    // Procurar por badge duplicado
    if (strpos($conteudo, 'badge badge-') !== false) {
        $ocorrencias = substr_count($conteudo, 'badge badge-');
        echo "  ℹ️  Badges encontrados: $ocorrencias\n";
        
        // Verificar se há badges duplicados próximos
        $padrao_badge = '/<span class=\"badge badge-[^"]*\">[^<]*<\\/span>/';
        if (preg_match_all($padrao_badge, $conteudo, $matches)) {
            echo "  ℹ️  Badges identificados: " . count($matches[0]) . "\n";
            
            // Verificar se há badges duplicados
            $badges_unicos = array_unique($matches[0]);
            if (count($badges_unicos) < count($matches[0])) {
                echo "  ⚠️  Há badges duplicados\n";
                
                // Remover badges duplicados (manter apenas o primeiro de cada)
                $conteudo_corrigido = '';
                $badges_ja_adicionados = [];
                
                $linhas = explode("\n", $conteudo);
                foreach ($linhas as $linha) {
                    if (preg_match($padrao_badge, $linha, $match)) {
                        $badge = $match[0];
                        if (!in_array($badge, $badges_ja_adicionados)) {
                            $badges_ja_adicionados[] = $badge;
                            $conteudo_corrigido .= $linha . "\n";
                        } else {
                            // Remover badge duplicado
                            $linha_corrigida = str_replace($badge, '', $linha);
                            $conteudo_corrigido .= $linha_corrigida . "\n";
                        }
                    } else {
                        $conteudo_corrigido .= $linha . "\n";
                    }
                }
                
                $conteudo = $conteudo_corrigido;
                echo "  ✅ Badges duplicados removidos\n";
            }
        }
    }
    
    // Salvar o arquivo corrigido
    file_put_contents($arquivo_usuario, $conteudo);
    echo "  ✅ Arquivo corrigido e salvo\n";
} else {
    echo "❌ Arquivo $arquivo_usuario NÃO encontrado\n";
}

echo "\n=== FIM DA CORREÇÃO ===\n";
?>
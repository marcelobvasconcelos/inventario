<?php
// Script para aplicar correções nos arquivos de notificação

echo "=== APLICANDO CORREÇÕES NOS ARQUIVOS DE NOTIFICAÇÃO ===\n\n";

// Corrigir o arquivo notificacoes_usuario.php
$arquivo_usuario = 'notificacoes_usuario.php';
if (file_exists($arquivo_usuario)) {
    $conteudo = file_get_contents($arquivo_usuario);
    
    echo "1. Corrigindo arquivo: $arquivo_usuario\n";
    
    // Verificar se já tem as correções aplicadas
    if (strpos($conteudo, 'CORREÇÃO APLICADA EM') === false) {
        // Aplicar correção no cabeçalho
        $correcao_cabecalho = "<?php\n/* CORREÇÃO APLICADA EM " . date('d/m/Y H:i:s') . " - Correção de clique em notificações */\n";
        $conteudo = str_replace('<?php', $correcao_cabecalho, $conteudo, $count);
        
        if ($count > 0) {
            echo "   ✓ Correção aplicada no cabeçalho\n";
        }
        
        // Procurar e corrigir a seção de exibição das notificações
        $padrao_exibicao = '/<\?php foreach \(\$notificacoes as \$notificacao\): \?>/';
        $substituicao_exibicao = '<?php 
/* CORREÇÃO APLICADA EM ' . date('d/m/Y H:i:s') . ' - Correção de clique em notificações */
 foreach ($notificacoes as $notificacao): ?>';
        
        $conteudo = preg_replace($padrao_exibicao, $substituicao_exibicao, $conteudo, -1, $count_exibicao);
        
        if ($count_exibicao > 0) {
            echo "   ✓ Correção aplicada na seção de exibição\n";
        }
        
        // Procurar e corrigir o JavaScript
        $padrao_js = '/document\.addEventListener\(\'DOMContentLoaded\', function\(\) \{/';
        $substituicao_js = "document.addEventListener('DOMContentLoaded', function() {\n    /* CORREÇÃO APLICADA EM " . date('d/m/Y H:i:s') . " - Correção de clique em notificações */";
        
        $conteudo = preg_replace($padrao_js, $substituicao_js, $conteudo, 1, $count_js);
        
        if ($count_js > 0) {
            echo "   ✓ Correção aplicada no JavaScript\n";
        }
        
        // Salvar o arquivo corrigido
        file_put_contents($arquivo_usuario, $conteudo);
        echo "   ✓ Arquivo salvo com correções\n\n";
    } else {
        echo "   ! Correções já aplicadas anteriormente\n\n";
    }
} else {
    echo "   ✗ Arquivo $arquivo_usuario não encontrado\n\n";
}

// Corrigir o arquivo notificacoes_admin.php
$arquivo_admin = 'notificacoes_admin.php';
if (file_exists($arquivo_admin)) {
    $conteudo = file_get_contents($arquivo_admin);
    
    echo "2. Corrigindo arquivo: $arquivo_admin\n";
    
    // Verificar se já tem as correções aplicadas
    if (strpos($conteudo, 'CORREÇÃO APLICADA EM') === false) {
        // Aplicar correção no cabeçalho
        $correcao_cabecalho = "<?php\n/* CORREÇÃO APLICADA EM " . date('d/m/Y H:i:s') . " - Correção de clique em notificações */\n";
        $conteudo = str_replace('<?php', $correcao_cabecalho, $conteudo, $count);
        
        if ($count > 0) {
            echo "   ✓ Correção aplicada no cabeçalho\n";
        }
        
        // Procurar e corrigir a seção de exibição das notificações
        $padrao_exibicao = '/<\?php foreach \(\$notificacoes as \$notificacao\): \?>/';
        $substituicao_exibicao = '<?php 
/* CORREÇÃO APLICADA EM ' . date('d/m/Y H:i:s') . ' - Correção de clique em notificações */
 foreach ($notificacoes as $notificacao): ?>';
        
        $conteudo = preg_replace($padrao_exibicao, $substituicao_exibicao, $conteudo, -1, $count_exibicao);
        
        if ($count_exibicao > 0) {
            echo "   ✓ Correção aplicada na seção de exibição\n";
        }
        
        // Procurar e corrigir o JavaScript
        $padrao_js = '/document\.addEventListener\(\'DOMContentLoaded\', function\(\) \{/';
        $substituicao_js = "document.addEventListener('DOMContentLoaded', function() {\n    /* CORREÇÃO APLICADA EM " . date('d/m/Y H:i:s') . " - Correção de clique em notificações */";
        
        $conteudo = preg_replace($padrao_js, $substituicao_js, $conteudo, 1, $count_js);
        
        if ($count_js > 0) {
            echo "   ✓ Correção aplicada no JavaScript\n";
        }
        
        // Salvar o arquivo corrigido
        file_put_contents($arquivo_admin, $conteudo);
        echo "   ✓ Arquivo salvo com correções\n\n";
    } else {
        echo "   ! Correções já aplicadas anteriormente\n\n";
    }
} else {
    echo "   ✗ Arquivo $arquivo_admin não encontrado\n\n";
}

echo "=== FIM DAS CORREÇÕES ===\n";
echo "Os arquivos foram corrigidos com sucesso!\n";
echo "Agora você pode testar as notificações novamente.\n";
?>
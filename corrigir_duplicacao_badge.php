<?php
// Script para corrigir a duplicação do badge do status de confirmação

$arquivo = 'notificacoes_usuario.php';
$arquivo_backup = 'notificacoes_usuario.php.backup_duplicado';

if (file_exists($arquivo)) {
    // Fazer backup do arquivo original
    copy($arquivo, $arquivo_backup);
    echo "Backup criado: $arquivo_backup\n\n";
    
    $conteudo = file_get_contents($arquivo);
    $linhas = explode("\n", $conteudo);
    
    echo "=== CORRIGINDO DUPLICAÇÃO DO BADGE ===\n\n";
    
    // Identificar as linhas que precisam ser removidas (461-469)
    $linha_inicio = 461;
    $linha_fim = 469;
    
    echo "Removendo linhas $linha_inicio a $linha_fim que contêm o badge duplicado:\n";
    
    // Mostrar o conteúdo que será removido
    for ($i = $linha_inicio; $i <= $linha_fim; $i++) {
        if (isset($linhas[$i - 1])) {
            echo "  Linha $i: " . trim($linhas[$i - 1]) . "\n";
        }
    }
    
    // Remover as linhas
    $novas_linhas = [];
    for ($i = 0; $i < count($linhas); $i++) {
        $numero_linha = $i + 1;
        if ($numero_linha < $linha_inicio || $numero_linha > $linha_fim) {
            $novas_linhas[] = $linhas[$i];
        }
    }
    
    // Salvar o arquivo corrigido
    $conteudo_corrigido = implode("\n", $novas_linhas);
    file_put_contents($arquivo, $conteudo_corrigido);
    
    echo "\n=== ARQUIVO CORRIGIDO COM SUCESSO ===\n";
    echo "Badge duplicado removido.\n";
    echo "O badge que permaneceu é o que está junto com a data.\n";
    
    // Verificar se há erros de sintaxe
    echo "\n=== VERIFICANDO SINTAXE ===\n";
    $output = shell_exec("php -l $arquivo 2>&1");
    echo $output;
}
?>
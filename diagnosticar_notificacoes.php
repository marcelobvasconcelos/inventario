<?php
// Script para diagnosticar o problema com as notificações

echo "=== DIAGNÓSTICO DO PROBLEMA COM AS NOTIFICAÇÕES ===\n\n";

// Verificar se os arquivos de notificação existem
$arquivos = ['notificacoes_usuario.php', 'notificacoes_admin.php'];

foreach ($arquivos as $arquivo) {
    echo "1. Verificando arquivo: $arquivo\n";
    
    if (file_exists($arquivo)) {
        echo "   ✓ Arquivo encontrado\n";
        
        // Verificar o tamanho do arquivo
        $tamanho = filesize($arquivo);
        echo "   Tamanho: " . number_format($tamanho) . " bytes\n";
        
        // Ler as primeiras e últimas linhas
        $conteudo = file_get_contents($arquivo);
        $linhas = explode("\n", $conteudo);
        
        echo "   Total de linhas: " . count($linhas) . "\n";
        echo "   Primeiras 5 linhas:\n";
        for ($i = 0; $i < min(5, count($linhas)); $i++) {
            echo "     " . ($i + 1) . ": " . trim($linhas[$i]) . "\n";
        }
        
        echo "   Últimas 5 linhas:\n";
        $total_linhas = count($linhas);
        for ($i = max(0, $total_linhas - 5); $i < $total_linhas; $i++) {
            echo "     " . ($i + 1) . ": " . trim($linhas[$i]) . "\n";
        }
    } else {
        echo "   ✗ Arquivo NÃO encontrado!\n";
    }
    
    echo "\n";
}

// Verificar se há erros de sintaxe nos arquivos
echo "2. Verificando erros de sintaxe:\n";

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "   Verificando $arquivo...\n";
        $output = shell_exec("php -l $arquivo 2>&1");
        echo "   $output\n";
    }
}

echo "\n";

// Verificar o banco de dados para ver se há notificações
echo "3. Verificando notificações no banco de dados:\n";

// Incluir a conexão com o banco de dados
require_once 'config/db.php';

$sql = "SELECT COUNT(*) as total FROM notificacoes_movimentacao";
$result = mysqli_query($link, $sql);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total = $row['total'];
    echo "   Total de notificações na tabela: $total\n";
    
    if ($total > 0) {
        // Verificar algumas notificações
        $sql_notif = "SELECT id, movimentacao_id, item_id, usuario_notificado_id, status_confirmacao FROM notificacoes_movimentacao LIMIT 5";
        $result_notif = mysqli_query($link, $sql_notif);
        
        if ($result_notif) {
            echo "   Amostra de notificações:\n";
            while ($row_notif = mysqli_fetch_assoc($result_notif)) {
                echo "     ID: " . $row_notif['id'] . 
                     ", Mov: " . $row_notif['movimentacao_id'] . 
                     ", Item: " . $row_notif['item_id'] . 
                     ", Usuário: " . $row_notif['usuario_notificado_id'] . 
                     ", Status: " . $row_notif['status_confirmacao'] . "\n";
            }
        }
    }
} else {
    echo "   Erro ao consultar notificações: " . mysqli_error($link) . "\n";
}

mysqli_close($link);

echo "\n";

// Verificar arquivos da API relacionados às notificações
echo "4. Verificando arquivos da API:\n";

$api_files = glob("api/*.php");
$api_notificacoes = [];
foreach ($api_files as $file) {
    if (strpos(basename($file), 'notific') !== false) {
        $api_notificacoes[] = $file;
    }
}

if (!empty($api_notificacoes)) {
    foreach ($api_notificacoes as $file) {
        echo "   Encontrado: $file\n";
        if (file_exists($file)) {
            $tamanho = filesize($file);
            echo "     Tamanho: " . number_format($tamanho) . " bytes\n";
        }
    }
} else {
    echo "   Nenhum arquivo da API relacionado a notificações encontrado\n";
}

echo "\n";

// Verificar permissões dos arquivos
echo "5. Verificando permissões dos arquivos:\n";

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        $perms = fileperms($arquivo);
        echo "   $arquivo: " . substr(sprintf('%o', $perms), -4) . "\n";
    }
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>
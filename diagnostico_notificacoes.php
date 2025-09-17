<?php
// Script de diagnóstico para verificar porque as notificações não estão aparecendo

// Iniciar sessão antes de qualquer saída
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "=== DIAGNÓSTICO DAS NOTIFICAÇÕES ===\n\n";

// 1. Verificar se o usuário está logado
if (!isset($_SESSION['id'])) {
    echo "❌ Usuário NÃO está logado\n";
    // Tentar conectar ao banco de dados mesmo assim para verificar dados
} else {
    echo "✅ Usuário está logado (ID: " . $_SESSION['id'] . ")\n";
}

// 2. Verificar conexão com o banco de dados
require_once 'config/db.php';
echo "\n--- Conexão com Banco de Dados ---\n";
if ($pdo) {
    echo "✅ Conexão PDO estabelecida\n";
} else {
    echo "❌ Falha na conexão PDO\n";
    exit;
}

// 3. Verificar se há notificações no banco de dados (independentemente do usuário)
echo "\n--- Verificando Notificações no Banco de Dados ---\n";
$sql_total = "SELECT COUNT(*) as total FROM notificacoes_movimentacao";
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute();
$total_geral = $stmt_total->fetchColumn();

echo "Total geral de notificações no banco: $total_geral\n";

if ($total_geral > 0) {
    echo "✅ Há notificações no banco de dados\n";
    
    // Verificar algumas notificações
    $sql_notif = "
        SELECT 
            nm.id, nm.status_confirmacao as notif_status, nm.data_notificacao, nm.usuario_notificado_id,
            i.nome as item_nome, i.patrimonio_novo,
            mov.usuario_id as admin_id
        FROM notificacoes_movimentacao nm
        JOIN itens i ON nm.item_id = i.id
        JOIN movimentacoes mov ON nm.movimentacao_id = mov.id
        ORDER BY nm.data_notificacao DESC
        LIMIT 5
    ";
    $stmt_notif = $pdo->prepare($sql_notif);
    $stmt_notif->execute();
    $notificacoes = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nAmostra das notificações:\n";
    foreach ($notificacoes as $notif) {
        echo "  ID: " . $notif['id'] . 
             " | Status: " . $notif['notif_status'] . 
             " | Usuário: " . $notif['usuario_notificado_id'] .
             " | Item: " . $notif['item_nome'] . 
             " | Data: " . $notif['data_notificacao'] . "\n";
    }
} else {
    echo "❌ Não há notificações no banco de dados\n";
}

// 4. Verificar conteúdo do arquivo de notificações
echo "\n--- Verificando Arquivo de Notificações ---\n";
$arquivo_usuario = 'notificacoes_usuario.php';
if (file_exists($arquivo_usuario)) {
    echo "✅ Arquivo notificacoes_usuario.php encontrado\n";
    
    $conteudo = file_get_contents($arquivo_usuario);
    $linhas = explode("\n", $conteudo);
    echo "Total de linhas no arquivo: " . count($linhas) . "\n";
    
    // Verificar se há a consulta SQL
    if (strpos($conteudo, 'notificacoes_movimentacao nm') !== false) {
        echo "✅ Consulta SQL para notificações encontrada\n";
    } else {
        echo "❌ Consulta SQL para notificações NÃO encontrada\n";
    }
    
    // Verificar se há processamento de dados
    if (strpos($conteudo, '$notificacoes = ') !== false) {
        echo "✅ Array de notificações encontrado\n";
    } else {
        echo "❌ Array de notificações NÃO encontrado\n";
    }
    
    // Verificar se há erros de sintaxe
    echo "\n--- Verificando Erros de Sintaxe ---\n";
    $output = shell_exec("php -l $arquivo_usuario 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Nenhum erro de sintaxe encontrado\n";
    } else {
        echo "❌ Erros de sintaxe encontrados:\n$output\n";
    }
    
} else {
    echo "❌ Arquivo notificacoes_usuario.php NÃO encontrado\n";
}

// 5. Verificar se há usuários no banco de dados
echo "\n--- Verificando Usuários ---\n";
$sql_usuarios = "SELECT id, nome, status FROM usuarios ORDER BY id";
$stmt_usuarios = $pdo->prepare($sql_usuarios);
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

echo "Usuários no banco de dados:\n";
foreach ($usuarios as $usuario) {
    echo "  ID: " . $usuario['id'] . 
         " | Nome: " . $usuario['nome'] . 
         " | Status: " . $usuario['status'] . "\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";

?>
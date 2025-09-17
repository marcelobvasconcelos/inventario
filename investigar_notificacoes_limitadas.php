<?php
// Script para investigar por que apenas a última notificação está aparecendo

echo "=== INVESTIGAÇÃO: POR QUE APENAS A ÚLTIMA NOTIFICAÇÃO APARECE ===\n\n";

// Simular sessão de usuário
session_start();
$_SESSION['id'] = 7; // Usuário teste gestor

// Conectar ao banco de dados
require_once 'config/db.php';

echo "--- ANÁLISE DETALHADA DAS NOTIFICAÇÕES ---\n";

// 1. Verificar a estrutura exata das tabelas
echo "\n1. Estrutura da tabela notificacoes_movimentacao:\n";
$sql_estrutura = "SHOW CREATE TABLE notificacoes_movimentacao";
$stmt_estrutura = $pdo->prepare($sql_estrutura);
$stmt_estrutura->execute();
$estrutura = $stmt_estrutura->fetch(PDO::FETCH_ASSOC);

if ($estrutura) {
    echo "Estrutura:\n" . $estrutura['Create Table'] . "\n";
    
    // Verificar se há PRIMARY KEY e AUTO_INCREMENT
    if (strpos($estrutura['Create Table'], 'PRIMARY KEY') !== false) {
        echo "✅ Possui PRIMARY KEY\n";
    } else {
        echo "❌ NÃO possui PRIMARY KEY\n";
    }
    
    if (strpos($estrutura['Create Table'], 'AUTO_INCREMENT') !== false) {
        echo "✅ Possui AUTO_INCREMENT\n";
    } else {
        echo "❌ NÃO possui AUTO_INCREMENT\n";
    }
}

// 2. Verificar os dados reais na tabela
echo "\n2. Dados na tabela notificacoes_movimentacao:\n";
$sql_dados = "
    SELECT 
        id, movimentacao_id, item_id, usuario_notificado_id, 
        status_confirmacao, data_notificacao
    FROM notificacoes_movimentacao 
    WHERE usuario_notificado_id = ?
    ORDER BY data_notificacao DESC
    LIMIT 10
";
$stmt_dados = $pdo->prepare($sql_dados);
$stmt_dados->execute([$_SESSION['id']]);
$dados = $stmt_dados->fetchAll(PDO::FETCH_ASSOC);

echo "Amostra das notificações (ordenadas por data):\n";
foreach ($dados as $i => $row) {
    echo "  " . ($i + 1) . ". ID: " . $row['id'] . 
         " | Mov: " . $row['movimentacao_id'] . 
         " | Item: " . $row['item_id'] . 
         " | Status: " . $row['status_confirmacao'] . 
         " | Data: " . $row['data_notificacao'] . "\n";
}

// 3. Verificar se há IDs duplicados
echo "\n3. Verificando IDs duplicados:\n";
$sql_ids = "
    SELECT id, COUNT(*) as count 
    FROM notificacoes_movimentacao 
    WHERE usuario_notificado_id = ?
    GROUP BY id 
    HAVING COUNT(*) > 1
";
$stmt_ids = $pdo->prepare($sql_ids);
$stmt_ids->execute([$_SESSION['id']]);
$ids_duplicados = $stmt_ids->fetchAll(PDO::FETCH_ASSOC);

if (count($ids_duplicados) > 0) {
    echo "❌ IDs duplicados encontrados:\n";
    foreach ($ids_duplicados as $dup) {
        echo "  ID " . $dup['id'] . " aparece " . $dup['count'] . " vezes\n";
    }
} else {
    echo "✅ Nenhum ID duplicado encontrado\n";
}

// 4. Verificar se há movimentacao_id duplicados
echo "\n4. Verificando movimentacao_id duplicados:\n";
$sql_mov_duplicados = "
    SELECT movimentacao_id, COUNT(*) as count 
    FROM notificacoes_movimentacao 
    WHERE usuario_notificado_id = ?
    GROUP BY movimentacao_id 
    HAVING COUNT(*) > 1
";
$stmt_mov_duplicados = $pdo->prepare($sql_mov_duplicados);
$stmt_mov_duplicados->execute([$_SESSION['id']]);
$mov_duplicados = $stmt_mov_duplicados->fetchAll(PDO::FETCH_ASSOC);

if (count($mov_duplicados) > 0) {
    echo "❌ movimentacao_id duplicados encontrados:\n";
    foreach ($mov_duplicados as $dup) {
        echo "  Movimentação " . $dup['movimentacao_id'] . " aparece " . $dup['count'] . " vezes\n";
    }
} else {
    echo "✅ Nenhum movimentacao_id duplicado encontrado\n";
}

// 5. Verificar se há item_id duplicados
echo "\n5. Verificando item_id duplicados:\n";
$sql_item_duplicados = "
    SELECT item_id, COUNT(*) as count 
    FROM notificacoes_movimentacao 
    WHERE usuario_notificado_id = ?
    GROUP BY item_id 
    HAVING COUNT(*) > 1
";
$stmt_item_duplicados = $pdo->prepare($sql_item_duplicados);
$stmt_item_duplicados->execute([$_SESSION['id']]);
$item_duplicados = $stmt_item_duplicados->fetchAll(PDO::FETCH_ASSOC);

if (count($item_duplicados) > 0) {
    echo "ℹ️ item_id duplicados encontrados (isso pode ser normal):\n";
    foreach ($item_duplicados as $dup) {
        echo "  Item " . $dup['item_id'] . " aparece " . $dup['count'] . " vezes\n";
    }
} else {
    echo "ℹ️ Nenhum item_id duplicado encontrado\n";
}

// 6. Verificar a consulta exata usada no arquivo
echo "\n6. Simulando consulta do arquivo de notificações:\n";

$sql_consulta_original = "
    SELECT 
        nm.id, nm.status_confirmacao as notif_status, nm.justificativa_usuario, nm.resposta_admin,
        nm.data_notificacao, nm.data_atualizacao,
        i.id as item_id, i.nome as item_nome, i.patrimonio_novo, i.patrimonio_secundario, i.estado, i.observacao,
        i.status_confirmacao as item_status,
        l.nome as local_nome,
        resp.nome as responsavel_nome,
        mov.usuario_id as admin_id,
        mov.usuario_anterior_id,
        admin_user.nome as admin_nome,
        nm.usuario_notificado_id,
        user_notified.nome as usuario_notificado_nome,
        mov.data_movimentacao
    FROM notificacoes_movimentacao nm
    JOIN itens i ON nm.item_id = i.id
    JOIN movimentacoes mov ON nm.movimentacao_id = mov.id
    JOIN usuarios admin_user ON mov.usuario_id = admin_user.id
    JOIN usuarios user_notified ON nm.usuario_notificado_id = user_notified.id
    LEFT JOIN locais l ON i.local_id = l.id
    LEFT JOIN usuarios resp ON i.responsavel_id = resp.id
    WHERE nm.usuario_notificado_id = ?
    ORDER BY nm.data_notificacao DESC
";

$stmt_consulta = $pdo->prepare($sql_consulta_original);
$stmt_consulta->execute([$_SESSION['id']]);
$resultados = $stmt_consulta->fetchAll(PDO::FETCH_ASSOC);

echo "Total de resultados da consulta: " . count($resultados) . "\n";

if (count($resultados) > 0) {
    echo "Primeiros resultados:\n";
    foreach (array_slice($resultados, 0, 5) as $i => $row) {
        echo "  " . ($i + 1) . ". Notificação ID: " . $row['id'] . 
             " | Item: " . $row['item_nome'] . 
             " | Status: " . $row['notif_status'] . 
             " | Data: " . $row['data_notificacao'] . "\n";
    }
} else {
    echo "❌ Nenhum resultado encontrado na consulta\n";
}

// 7. Verificar se há GROUP BY escondendo resultados
echo "\n7. Verificando se há GROUP BY na consulta:\n";
if (strpos($sql_consulta_original, 'GROUP BY') !== false) {
    echo "❌ Há GROUP BY na consulta que pode estar agrupando resultados\n";
    
    // Procurar onde está o GROUP BY
    $linhas = explode("\n", $sql_consulta_original);
    foreach ($linhas as $num => $linha) {
        if (strpos($linha, 'GROUP BY') !== false) {
            echo "  Linha " . ($num + 1) . ": " . trim($linha) . "\n";
        }
    }
} else {
    echo "✅ Não há GROUP BY na consulta\n";
}

// 8. Verificar se há LIMIT escondendo resultados
echo "\n8. Verificando se há LIMIT na consulta principal:\n";
if (strpos($sql_consulta_original, 'LIMIT') !== false) {
    echo "❌ Há LIMIT na consulta principal que pode estar limitando resultados\n";
    
    // Procurar onde está o LIMIT
    $linhas = explode("\n", $sql_consulta_original);
    foreach ($linhas as $num => $linha) {
        if (strpos($linha, 'LIMIT') !== false) {
            echo "  Linha " . ($num + 1) . ": " . trim($linha) . "\n";
        }
    }
} else {
    echo "✅ Não há LIMIT na consulta principal\n";
}

// 9. Verificar se há DISTINCT escondendo resultados
echo "\n9. Verificando se há DISTINCT na consulta:\n";
if (strpos($sql_consulta_original, 'DISTINCT') !== false) {
    echo "❌ Há DISTINCT na consulta que pode estar removendo duplicatas\n";
} else {
    echo "✅ Não há DISTINCT na consulta\n";
}

// 10. Verificar se há condições escondendo resultados
echo "\n10. Verificando condições na consulta:\n";
$condicoes_suspeitas = ['status_confirmacao', 'data_notificacao', 'movimentacao_id'];
foreach ($condicoes_suspeitas as $condicao) {
    if (strpos($sql_consulta_original, $condicao) !== false && 
        strpos($sql_consulta_original, "WHERE") !== false &&
        strpos($sql_consulta_original, $condicao) < strpos($sql_consulta_original, "ORDER BY")) {
        echo "⚠️  Há condição de $condicao que pode estar filtrando resultados\n";
    }
}

echo "\n--- VERIFICAÇÃO ADICIONAL ---\n";

// 11. Verificar se há triggers ou stored procedures afetando os resultados
echo "\n11. Verificando triggers:\n";
$sql_triggers = "SHOW TRIGGERS LIKE 'notificacoes_movimentacao'";
$stmt_triggers = $pdo->prepare($sql_triggers);
$stmt_triggers->execute();
$triggers = $stmt_triggers->fetchAll(PDO::FETCH_ASSOC);

if (count($triggers) > 0) {
    echo "⚠️  Triggers encontrados na tabela notificacoes_movimentacao:\n";
    foreach ($triggers as $trigger) {
        echo "  - " . $trigger['Trigger'] . "\n";
    }
} else {
    echo "✅ Nenhum trigger encontrado\n";
}

// 12. Verificar se há views afetando os resultados
echo "\n12. Verificando views:\n";
$sql_views = "SHOW FULL TABLES WHERE Table_type = 'VIEW'";
$stmt_views = $pdo->prepare($sql_views);
$stmt_views->execute();
$views = $stmt_views->fetchAll(PDO::FETCH_ASSOC);

$views_notificacoes = [];
foreach ($views as $view) {
    if (strpos(strtolower(array_values($view)[0]), 'notific') !== false) {
        $views_notificacoes[] = $view;
    }
}

if (count($views_notificacoes) > 0) {
    echo "⚠️  Views relacionadas a notificações encontradas:\n";
    foreach ($views_notificacoes as $view) {
        echo "  - " . array_values($view)[0] . "\n";
    }
} else {
    echo "✅ Nenhuma view relacionada a notificações encontrada\n";
}

echo "\n=== FIM DA INVESTIGAÇÃO ===\n";

?>
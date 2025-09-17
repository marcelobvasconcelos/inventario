<?php
// Script para simular exatamente o que acontece quando acessamos sem parâmetros

echo "=== SIMULAÇÃO DO ACESSO SEM PARÂMETROS ===\n\n";

// Simular acesso sem parâmetros
$_GET = []; // Sem parâmetros na URL

echo "--- SIMULANDO DEFINIÇÃO DA VARIÁVEL ---\n";

// Simular exatamente a definição da variável como no arquivo
$notificacao_unica_id = isset($_GET['notif_id']) ? (int)$_GET['notif_id'] : 0;

echo "Valor de \$notificacao_unica_id: $notificacao_unica_id\n";

if ($notificacao_unica_id > 0) {
    echo "❌ Condição verdadeira: Exibir apenas notificação ID $notificacao_unica_id\n";
} else {
    echo "✅ Condição falsa: Exibir TODAS as notificações\n";
}

echo "\n--- SIMULANDO CONSULTA AO BANCO DE DADOS ---\n";

// Conectar ao banco de dados
require_once 'config/db.php';

// Simular sessão de usuário
$_SESSION['id'] = 7; // Usuário teste gestor
$usuario_logado_id = $_SESSION['id'];

echo "Usuário logado ID: $usuario_logado_id\n";

// Simular a consulta SQL exata que seria usada
$sql = "
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
";

$params = [$usuario_logado_id];

// Verificar se é para exibir uma notificação única ou todas
if ($notificacao_unica_id > 0) {
    echo "❌ Modo: Exibir apenas uma notificação (ID: $notificacao_unica_id)\n";
    $sql .= " AND nm.id = ?";
    $params[] = $notificacao_unica_id;
} else {
    echo "✅ Modo: Exibir todas as notificações (ordenadas por data)\n";
    $sql .= " ORDER BY nm.data_notificacao DESC";
}

echo "\nConsulta SQL:\n$sql\n";
echo "Parâmetros: " . json_encode($params) . "\n";

// Executar a consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notificacoes_movimentacao_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nTotal de notificações encontradas: " . count($notificacoes_movimentacao_raw) . "\n";

if (count($notificacoes_movimentacao_raw) > 0) {
    echo "✅ Consulta bem-sucedida\n";
    
    echo "\nAmostra das notificações:\n";
    foreach (array_slice($notificacoes_movimentacao_raw, 0, 10) as $i => $notif) {
        echo "  " . ($i + 1) . ". ID: " . $notif['id'] . 
             " | Item: " . $notif['item_nome'] . 
             " | Status: " . $notif['notif_status'] . 
             " | Data: " . $notif['data_notificacao'] . "\n";
    }
    
    echo "\n--- SIMULANDO PROCESSAMENTO DAS NOTIFICAÇÕES ---\n";
    
    // Processar as notificações como o arquivo faria
    $notificacoes_para_exibir = [];
    foreach ($notificacoes_movimentacao_raw as $nm) {
        // Buscar status do item diretamente da tabela itens
        $sql_item_status = "SELECT status_confirmacao FROM itens WHERE id = ?";
        $stmt_item_status = $pdo->prepare($sql_item_status);
        $stmt_item_status->execute([$nm['item_id']]);
        $item_status_row = $stmt_item_status->fetch(PDO::FETCH_ASSOC);
        $item_status = $item_status_row ? $item_status_row['status_confirmacao'] : '';
        
        if (empty($item_status)) {
            $status_fmt = 'Pendente';
        } elseif ($item_status === 'Nao Confirmado') {
            $status_fmt = 'Não Confirmado';
        } elseif ($item_status === 'Confirmado') {
            $status_fmt = 'Confirmado';
        } elseif ($item_status === 'Movimento Desfeito') {
            $status_fmt = 'Movimento Desfeito';
        } elseif ($item_status === 'Em Disputa') {
            $status_fmt = 'Em Disputa';
        } else {
            $status_fmt = ucfirst($item_status);
        }
        
        $notificacoes_para_exibir[] = [
            'id' => $nm['id'],
            'tipo' => 'transferencia',
            'mensagem' => "Movimentação do item: " . htmlspecialchars($nm['item_nome']) . " (Patrimônio: " . htmlspecialchars($nm['patrimonio_novo']) . "). Status: " . htmlspecialchars($status_fmt),
            'status' => $status_fmt,
            'data_envio' => $nm['data_notificacao'],
            'administrador_nome' => $nm['admin_nome'],
            'assunto_titulo' => 'Movimentação de Item',
            'assunto_resumo' => "Item: " . htmlspecialchars($nm['item_nome']) . " - Status: " . htmlspecialchars($status_fmt),
            'justificativa' => $nm['justificativa_usuario'],
            'data_resposta' => $nm['data_atualizacao'],
            'admin_reply' => $nm['resposta_admin'],
            'admin_reply_date' => $nm['data_atualizacao'],
            'detalhes_itens' => [
                [
                    'id' => $nm['item_id'],
                    'status_confirmacao' => $item_status, // valor do item
                    'justificativa_usuario' => $nm['justificativa_usuario'],
                    'admin_reply' => $nm['resposta_admin'],
                    'nome' => $nm['item_nome'],
                    'patrimonio_novo' => $nm['patrimonio_novo'],
                    'patrimonio_secundario' => $nm['patrimonio_secundario'],
                    'estado' => $nm['estado'],
                    'observacao' => $nm['observacao'],
                    'local_nome' => $nm['local_nome'],
                    'responsavel_nome' => $nm['responsavel_nome'],
                    'usuario_anterior_id' => $nm['usuario_anterior_id'],
                    'data_justificativa' => $nm['data_atualizacao'],
                    'data_admin_reply' => $nm['data_atualizacao'],
                ]
            ],
            'data_item_statuses' => $status_fmt
        ];
    }
    
    echo "Total de notificações processadas para exibição: " . count($notificacoes_para_exibir) . "\n";
    echo "✅ Processamento concluído com sucesso\n";
    
    echo "\n--- VERIFICANDO EXIBIÇÃO ---\n";
    
    if (count($notificacoes_para_exibir) > 0) {
        echo "✅ Seriam exibidas " . count($notificacoes_para_exibir) . " notificações\n";
        
        // Verificar se há problema com o loop de exibição
        echo "\nVerificando loop de exibição:\n";
        echo "foreach (\$notificacoes_para_exibir as \$notificacao) {\n";
        echo "  // Exibir notificação\n";
        echo "  // ID: " . $notificacoes_para_exibir[0]['id'] . "\n";
        echo "  // Título: " . $notificacoes_para_exibir[0]['assunto_titulo'] . "\n";
        echo "}\n";
        
        echo "\nTotal de iterações no loop: " . count($notificacoes_para_exibir) . "\n";
        
    } else {
        echo "❌ Nenhuma notificação seria exibida\n";
    }
    
} else {
    echo "❌ Nenhuma notificação encontrada\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Quando acessado sem parâmetros (?notif_id=N), o sistema:\n";
echo "1. Define \$notificacao_unica_id = 0\n";
echo "2. Condição if (\$notificacao_unica_id > 0) é falsa\n";
echo "3. Exibe todas as notificações ordenadas por data\n";
echo "4. Deveriam ser exibidas " . count($notificacoes_movimentacao_raw) . " notificações\n";

echo "\nSe você está vendo apenas a última notificação, o problema pode ser:\n";
echo "1. Alguma condição no loop de exibição que limita a quantidade\n";
echo "2. Algum CSS/JavaScript que esconde as outras notificações\n";
echo "3. Algum erro no template que impede a exibição completa\n";
echo "4. Alguma condição de filtro que está ocultando as outras\n";

echo "\n=== FIM DA SIMULAÇÃO ===\n";

?>
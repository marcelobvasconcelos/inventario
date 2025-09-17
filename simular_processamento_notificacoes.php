<?php
// Script para simular exatamente o que o arquivo notificacoes_usuario.php está fazendo

echo "=== SIMULAÇÃO DO PROCESSAMENTO DAS NOTIFICAÇÕES ===\n\n";

// Simular sessão
session_start();
$_SESSION['id'] = 7; // Usuário teste gestor

// Conectar ao banco de dados
require_once 'config/db.php';

echo "--- Simulando consulta de notificações ---\n";

// Simular a consulta exata do arquivo
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
    ORDER BY nm.data_notificacao DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id']]);
$notificacoes_movimentacao_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total de notificações encontradas: " . count($notificacoes_movimentacao_raw) . "\n";

if (count($notificacoes_movimentacao_raw) > 0) {
    echo "✅ Notificações encontradas para o usuário\n";
    
    // Simular o processamento exato do arquivo
    echo "\n--- Simulando processamento das notificações ---\n";
    
    $notificacoes_para_exibir = [];
    foreach ($notificacoes_movimentacao_raw as $nm) {
        // Buscar status do item diretamente da tabela itens (como faz o arquivo)
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
    
    echo "Total de notificações processadas: " . count($notificacoes_para_exibir) . "\n";
    echo "✅ Processamento concluído com sucesso\n";
    
    // Verificar se as notificações seriam exibidas
    echo "\n--- Verificando se as notificações seriam exibidas ---\n";
    
    if (count($notificacoes_para_exibir) > 0) {
        echo "✅ Deveriam ser exibidas " . count($notificacoes_para_exibir) . " notificações\n";
        
        // Mostrar amostra
        echo "\nAmostra das primeiras notificações:\n";
        foreach (array_slice($notificacoes_para_exibir, 0, 3) as $notif) {
            echo "  ID: " . $notif['id'] . 
                 " | Status: " . $notif['status'] . 
                 " | Item: " . strip_tags($notif['assunto_resumo']) . "\n";
        }
    } else {
        echo "❌ Nenhuma notificação seria exibida\n";
    }
    
} else {
    echo "❌ Nenhuma notificação encontrada para o usuário\n";
}

// Verificar se há erros no arquivo
echo "\n--- Verificando erros no arquivo ---\n";
$output = shell_exec("php -l notificacoes_usuario.php 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "✅ Nenhum erro de sintaxe encontrado\n";
} else {
    echo "❌ Erros de sintaxe encontrados:\n$output\n";
}

echo "\n=== FIM DA SIMULAÇÃO ===\n";

?>
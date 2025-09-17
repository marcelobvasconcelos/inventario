<?php
// Script para simular exatamente o que o arquivo de notificações está fazendo com os dados

echo "=== SIMULAÇÃO EXATA DO PROCESSAMENTO DAS NOTIFICAÇÕES ===\n\n";

// Simular sessão de usuário
session_start();
$_SESSION['id'] = 7; // Usuário teste gestor

// Conectar ao banco de dados
require_once 'config/db.php';

echo "--- PROCESSAMENTO EXATO DAS NOTIFICAÇÕES ---\n";

// Reproduzir exatamente a consulta do arquivo de notificações
$sql_consulta = "
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

$stmt_consulta = $pdo->prepare($sql_consulta);
$stmt_consulta->execute([$_SESSION['id']]);
$notificacoes_movimentacao_raw = $stmt_consulta->fetchAll(PDO::FETCH_ASSOC);

echo "Total de notificações brutas encontradas: " . count($notificacoes_movimentacao_raw) . "\n";

if (count($notificacoes_movimentacao_raw) > 0) {
    echo "✅ Consulta retornou dados\n";
    
    // Simular exatamente o processamento do array $notificacoes_movimentacao_raw
    echo "\n--- PROCESSANDO ARRAY RAW PARA EXIBIÇÃO ---\n";
    
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
    
    // Verificar se as notificações seriam exibidas
    echo "\n--- VERIFICANDO EXIBIÇÃO DAS NOTIFICAÇÕES ---\n";
    
    if (count($notificacoes_para_exibir) > 0) {
        echo "✅ Seriam exibidas " . count($notificacoes_para_exibir) . " notificações\n";
        
        // Mostrar amostra
        echo "\nAmostra das primeiras notificações:\n";
        foreach (array_slice($notificacoes_para_exibir, 0, 10) as $i => $notif) {
            echo "  " . ($i + 1) . ". ID: " . $notif['id'] . 
                 " | Status: " . $notif['status'] . 
                 " | Item: " . strip_tags($notif['assunto_resumo']) . "\n";
        }
        
        // Verificar se há alguma condição no arquivo que limita a exibição
        echo "\n--- VERIFICANDO CONDIÇÕES DE EXIBIÇÃO NO ARQUIVO ---\n";
        
        // Simular a verificação do arquivo de notificações
        $arquivo_usuario = 'notificacoes_usuario.php';
        if (file_exists($arquivo_usuario)) {
            $conteudo_arquivo = file_get_contents($arquivo_usuario);
            
            // Procurar por condições que possam limitar a exibição
            $condicoes = [
                'if ($notificacao_unica_id > 0)',
                'if (!$notificacao_unica_id)',
                'if (empty($notificacoes))',
                'if ($notificacao_unica_id == 0)',
                'if ($notificacao_unica_id != 0)',
                'WHERE 1=1'
            ];
            
            foreach ($condicoes as $condicao) {
                if (strpos($conteudo_arquivo, $condicao) !== false) {
                    echo "ℹ️ Condição encontrada: $condicao\n";
                    
                    // Verificar o contexto
                    $pos = strpos($conteudo_arquivo, $condicao);
                    $contexto = substr($conteudo_arquivo, max(0, $pos - 100), 200);
                    echo "   Contexto: " . str_replace(["\n", "\r", "\t"], " ", substr($contexto, 0, 80)) . "...\n";
                }
            }
            
            // Verificar se há variável $notificacao_unica_id
            if (strpos($conteudo_arquivo, 'notificacao_unica_id') !== false) {
                echo "⚠️ Variável \$notificacao_unica_id encontrada\n";
                
                // Verificar como ela é definida
                $padroes_definicao = [
                    '\$notificacao_unica_id = ',
                    'isset\(\$_GET\[\'notif_id\'\]\)'
                ];
                
                foreach ($padroes_definicao as $padrao) {
                    if (preg_match("/$padrao/", $conteudo_arquivo)) {
                        echo "✅ Definição encontrada: $padrao\n";
                    }
                }
            }
            
            // Verificar se há limitações no loop de exibição
            if (strpos($conteudo_arquivo, 'foreach ($notificacoes as $notificacao)') !== false) {
                echo "✅ Loop de exibição encontrado\n";
            } else {
                echo "❌ Loop de exibição NÃO encontrado\n";
            }
            
        } else {
            echo "❌ Arquivo notificacoes_usuario.php NÃO encontrado\n";
        }
        
    } else {
        echo "❌ Nenhuma notificação seria exibida\n";
    }
    
} else {
    echo "❌ Consulta NÃO retornou dados\n";
}

echo "\n=== VERIFICAÇÃO ADICIONAL ===\n";

// Verificar se há problema com a variável notificacao_unica_id
echo "\n--- VERIFICANDO VARIÁVEL notificacao_unica_id ---\n";

$arquivo_usuario = 'notificacoes_usuario.php';
if (file_exists($arquivo_usuario)) {
    $conteudo_arquivo = file_get_contents($arquivo_usuario);
    
    // Verificar como notificacao_unica_id é definida
    if (preg_match('/\\$notificacao_unica_id\\s*=\\s*isset\\(\\$_GET\\[\'notif_id\'\\]\\)/', $conteudo_arquivo)) {
        echo "✅ notificacao_unica_id definida corretamente\n";
    } else {
        echo "ℹ️ Verificando definição de notificacao_unica_id\n";
        
        // Procurar por todas as definições
        if (strpos($conteudo_arquivo, '$notificacao_unica_id = ') !== false) {
            echo "✅ Variável \$notificacao_unica_id encontrada\n";
            
            // Verificar contexto
            $pos = strpos($conteudo_arquivo, '$notificacao_unica_id = ');
            $linha = substr_count(substr($conteudo_arquivo, 0, $pos), "\n") + 1;
            echo "   Definida na linha $linha\n";
            
            // Mostrar a linha
            $linhas = explode("\n", $conteudo_arquivo);
            $linha_definicao = $linhas[$linha - 1];
            echo "   Código: " . trim($linha_definicao) . "\n";
        } else {
            echo "❌ Variável \$notificacao_unica_id NÃO encontrada\n";
        }
    }
    
    // Verificar se há condição que limita a exibição a uma notificação única
    if (strpos($conteudo_arquivo, 'if ($notificacao_unica_id > 0)') !== false) {
        echo "ℹ️ Condição para exibir notificação única encontrada\n";
        
        // Verificar contexto
        $pos = strpos($conteudo_arquivo, 'if ($notificacao_unica_id > 0)');
        $contexto = substr($conteudo_arquivo, max(0, $pos - 50), 100);
        echo "   Contexto: " . str_replace(["\n", "\r", "\t"], " ", $contexto) . "\n";
    }
    
    // Verificar se há loop de exibição condicional
    if (strpos($conteudo_arquivo, 'foreach ($notificacoes as $notificacao)') !== false) {
        echo "✅ Loop de exibição encontrado\n";
        
        // Verificar se está dentro de uma condição
        $pos_forelse = strpos($conteudo_arquivo, 'foreach ($notificacoes as $notificacao)');
        $conteudo_antes = substr($conteudo_arquivo, 0, $pos_forelse);
        
        // Contar chaves abertas e fechadas antes do foreach
        $chaves_abertas = substr_count($conteudo_antes, '{');
        $chaves_fechadas = substr_count($conteudo_antes, '}');
        
        echo "   Chaves abertas antes do foreach: $chaves_abertas\n";
        echo "   Chaves fechadas antes do foreach: $chaves_fechadas\n";
        echo "   Diferença: " . ($chaves_abertas - $chaves_fechadas) . "\n";
    }
    
    // Verificar se há limitação no loop de exibição
    if (strpos($conteudo_arquivo, 'break') !== false) {
        echo "⚠️ Comando break encontrado no arquivo\n";
        
        // Procurar todas as ocorrências
        $pos = 0;
        while (($pos = strpos($conteudo_arquivo, 'break', $pos)) !== false) {
            $linha = substr_count(substr($conteudo_arquivo, 0, $pos), "\n") + 1;
            echo "   break encontrado na linha $linha\n";
            $pos += 5; // Move para frente
        }
    }
}

echo "\n=== FIM DA SIMULAÇÃO ===\n";

?>
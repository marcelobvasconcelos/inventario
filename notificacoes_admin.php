<?php
// Inicia a sessão PHP e inclui a conexão com o banco de dados
require_once 'config/db.php';

// Redireciona para a página de login se o usuário não estiver logado ou não for Administrador
if (!isset($_SESSION["id"]) || $_SESSION["permissao"] != 'Administrador') {
    header("location: login.php");
    exit;
}

// --- Processamento de Ações do Administrador via AJAX ---
if(isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    // Log dos dados recebidos
    error_log("Dados recebidos via POST em notificacoes_admin.php: " . print_r($_POST, true));
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'new_item_status' => '', 'new_notif_status' => ''];

    // Validação mais robusta dos dados recebidos
    if(isset($_POST['action'], $_POST['notificacao_id'], $_POST['item_id'])){ // Adicionado item_id aqui
        $notificacao_movimentacao_id = filter_var($_POST['notificacao_id'], FILTER_VALIDATE_INT);
        $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
        $action = $_POST['action'];
        $admin_reply = isset($_POST['admin_reply']) ? trim($_POST['admin_reply']) : '';

        if (!$notificacao_movimentacao_id || !$item_id) {
            $response['message'] = 'ID de notificação ou item inválido.';
            echo json_encode($response);
            exit;
        }

        $administrador_logado_id = $_SESSION['id'];
        error_log("Valor de \$_SESSION['id']: " . print_r($_SESSION['id'], true)); // Log para debug

        // Verifica se o administrador logado existe na tabela usuarios
        if ($administrador_logado_id) {
            $stmt_check_admin = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND status = 'aprovado'");
            $stmt_check_admin->execute([$administrador_logado_id]);
            if (!$stmt_check_admin->fetch()) {
                $administrador_logado_id = null; // Invalida o ID se o usuário não for encontrado ou não estiver aprovado
            }
        }
        error_log("Valor de \$administrador_logado_id após validação: " . print_r($administrador_logado_id, true)); // Log para debug

        $pdo->beginTransaction();
        try {
            // Busca informações da notificação de movimentação e do item para garantir que existam
            $sql_notif_mov_info = "SELECT nm.item_id, nm.movimentacao_id, nm.status_confirmacao as notif_status, i.status_confirmacao as item_status FROM notificacoes_movimentacao nm JOIN itens i ON nm.item_id = i.id WHERE nm.id = ? AND nm.item_id = ?";
            $stmt_notif_mov_info = $pdo->prepare($sql_notif_mov_info);
            $stmt_notif_mov_info->execute([$notificacao_movimentacao_id, $item_id]);
            $notif_mov_data = $stmt_notif_mov_info->fetch(PDO::FETCH_ASSOC);

            if (!$notif_mov_data) {
                throw new Exception("Notificação de movimentação ou item não encontrado. notificacao_movimentacao_id: $notificacao_movimentacao_id, item_id: $item_id");
            }
            
            // Verifica se o status do ITEM permite resposta
            // O administrador só deve poder responder se o item estiver 'Nao Confirmado' ou 'Em Disputa'
            $item_status = $notif_mov_data['item_status'];
            error_log("Debug - Status do item: $item_status"); // Log para debug
            if ($item_status !== 'Nao Confirmado' && $item_status !== 'Em Disputa' && $item_status !== 'Pendente') {
                throw new Exception("Não é possível responder a um item com status '$item_status'. Apenas itens 'Não Confirmado', 'Em Disputa' ou 'Pendente' podem receber respostas.");
            }
            
            $movimentacao_id = $notif_mov_data['movimentacao_id'];
            $data_atualizacao = date('Y-m-d H:i:s');

            if ($action == 'responder_item_disputa') {
                if (empty($admin_reply)) {
                    throw new Exception("Por favor, forneça uma resposta para a disputa.");
                }
                
                // Adiciona log de debug
                error_log("Debug - Responder Item Disputa: notificacao_movimentacao_id=$notificacao_movimentacao_id, item_id=$item_id, administrador_logado_id=$administrador_logado_id, admin_reply=$admin_reply");
                
                // Verifica se o administrador logado tem um ID válido
                if (!$administrador_logado_id) {
                    throw new Exception("ID do administrador não encontrado na sessão.");
                }

                // Atualiza o registro em notificacoes_movimentacao com a resposta do admin e muda o status para 'Pendente'
                $new_item_status = 'Pendente';
                $sql_update_notif_mov = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, resposta_admin = ?, data_atualizacao = ? WHERE id = ?";
                $stmt_update_notif_mov = $pdo->prepare($sql_update_notif_mov);
                
                // Adiciona log de debug antes da execução
                error_log("Debug - Executando UPDATE notificacoes_movimentacao: " . json_encode([$new_item_status, $admin_reply, $data_atualizacao, $notificacao_movimentacao_id]));
                
                $stmt_update_notif_mov->execute([$new_item_status, $admin_reply, $data_atualizacao, $notificacao_movimentacao_id]);

                // O status do item na tabela 'itens' deve voltar para 'Pendente' para que o usuário possa reconfirmar
                $sql_update_item_main = "UPDATE itens SET status_confirmacao = 'Pendente' WHERE id = ?";
                $stmt_update_item_main = $pdo->prepare($sql_update_item_main);
                
                // Adiciona log de debug antes da execução
                error_log("Debug - Executando UPDATE itens: " . json_encode([$item_id]));
                
                $stmt_update_item_main->execute([$item_id]);

                // Insere a resposta do administrador no histórico
                $sql_insert_history = "INSERT INTO notificacoes_respostas_historico (notificacao_movimentacao_id, remetente_id, tipo_remetente, conteudo_resposta, data_resposta) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert_history = $pdo->prepare($sql_insert_history);
                
                // Adiciona log de debug antes da execução
                error_log("Debug - Executando INSERT notificacoes_respostas_historico: " . json_encode([$notificacao_movimentacao_id, $administrador_logado_id, 'admin', $admin_reply, $data_atualizacao]));
                
                $stmt_insert_history->execute([$notificacao_movimentacao_id, $administrador_logado_id, 'admin', $admin_reply, $data_atualizacao]);

                $response['message'] = "Resposta enviada para o item #{$item_id}. O status foi atualizado para 'Pendente' e o usuário foi notificado para uma nova confirmação.";
                $response['new_item_status'] = $new_item_status;

            } elseif ($action == 'desfazer_movimentacao_item') {
                // Busca os dados da movimentação original para reverter
                $sql_get_mov_details = "SELECT local_origem_id, usuario_anterior_id FROM movimentacoes WHERE id = ?";
                $stmt_get_mov_details = $pdo->prepare($sql_get_mov_details);
                $stmt_get_mov_details->execute([$movimentacao_id]);
                $mov_details = $stmt_get_mov_details->fetch(PDO::FETCH_ASSOC);

                if ($mov_details && !empty($mov_details['usuario_anterior_id'])) {
                    // Reverte o item para o local e responsável de origem da movimentação e status 'Confirmado'
                    $new_item_status = 'Confirmado';
                    $sql_update_item_main = "UPDATE itens SET local_id = ?, responsavel_id = ?, status_confirmacao = ? WHERE id = ?";
                    $stmt_update_item_main = $pdo->prepare($sql_update_item_main);
                    $stmt_update_item_main->execute([$mov_details['local_origem_id'], $mov_details['usuario_anterior_id'], $new_item_status, $item_id]);

                    // Atualiza o status na notificacoes_movimentacao para 'Confirmado'
                    $sql_update_notif_mov = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, data_atualizacao = ? WHERE id = ?";
                    $stmt_update_notif_mov = $pdo->prepare($sql_update_notif_mov);
                    $stmt_update_notif_mov->execute([$new_item_status, $data_atualizacao, $notificacao_movimentacao_id]);

                    $response['message'] = "Movimentação do item #{$item_id} desfeita. Status do item atualizado para Confirmado.";
                    $response['new_item_status'] = $new_item_status;
                } else {
                    throw new Exception("Não é possível desfazer a movimentação inicial deste item. Selecione outro responsável.");
                }
            } elseif ($action == 'atribuir_novo_responsavel') {
                $novo_responsavel_id = isset($_POST['novo_responsavel_id']) ? (int)$_POST['novo_responsavel_id'] : 0;
                if (!$novo_responsavel_id) {
                    throw new Exception('Selecione um novo responsável válido.');
                }
                // Obter dados atuais do item
                $stmt_item = $pdo->prepare('SELECT local_id, responsavel_id FROM itens WHERE id = ?');
                $stmt_item->execute([$item_id]);
                $item_row = $stmt_item->fetch(PDO::FETCH_ASSOC);
                if (!$item_row) {
                    throw new Exception('Item não encontrado.');
                }
                $local_atual = (int)$item_row['local_id'];
                $responsavel_anterior = (int)$item_row['responsavel_id'];

                // Atualiza o item para o novo responsável e mantém Pendente
                $stmt_upd_item = $pdo->prepare("UPDATE itens SET responsavel_id = ?, status_confirmacao = 'Pendente' WHERE id = ?");
                $stmt_upd_item->execute([$novo_responsavel_id, $item_id]);

                // Insere nova movimentação (mesmo local)
                $stmt_mov = $pdo->prepare('INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id, usuario_destino_id, data_movimentacao) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt_mov->execute([$item_id, $local_atual, $local_atual, $administrador_logado_id, $responsavel_anterior, $novo_responsavel_id]);
                $nova_mov_id = $pdo->lastInsertId();

                // Cria nova notificação para o novo responsável
                $stmt_nm = $pdo->prepare("INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')");
                $stmt_nm->execute([$nova_mov_id, $item_id, $novo_responsavel_id]);

                // Marca a notificação antiga como 'Movimento Desfeito'
                $stmt_close_nm = $pdo->prepare("UPDATE notificacoes_movimentacao SET status_confirmacao = 'Movimento Desfeito', data_atualizacao = ? WHERE id = ?");
                $stmt_close_nm->execute([$data_atualizacao, $notificacao_movimentacao_id]);

                // Recupera o nome do novo responsável
                $stmt_user = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
                $stmt_user->execute([$novo_responsavel_id]);
                $novo_resp_nome = $stmt_user->fetchColumn();

                $response['message'] = 'Novo responsável atribuído e notificado.';
                $response['new_item_status'] = 'Pendente';
                $response['new_notif_status'] = 'Movimento Desfeito';
                $response['novo_responsavel_nome'] = $novo_resp_nome ?: '';
            } else {
                throw new Exception("Ação inválida.");
            }

            // --- Lógica para determinar o status geral da notificação (movimentacao_id) ---
            $new_notif_status = 'Pendente'; // Status padrão
            if($movimentacao_id) {
                // 2. Obter todos os status para essa movimentação
                $stmt_statuses = $pdo->prepare("SELECT status_confirmacao FROM notificacoes_movimentacao WHERE movimentacao_id = ?");
                $stmt_statuses->execute([$movimentacao_id]);
                $statuses = $stmt_statuses->fetchAll(PDO::FETCH_COLUMN);

                // 3. Calcular o status geral
                $unique_statuses = array_unique($statuses);
                if (in_array('Em Disputa', $unique_statuses) || in_array('Pendente', $unique_statuses)) {
                    $new_notif_status = 'Pendente'; // Se ainda há itens pendentes ou em disputa, a notificação geral fica pendente.
                } elseif (count($unique_statuses) == 1 && $unique_statuses[0] == 'Confirmado') {
                    $new_notif_status = 'Confirmado';
                }
                elseif (count($unique_statuses) == 1 && $unique_statuses[0] == 'Movimento Desfeito') {
                    $new_notif_status = 'Movimento Desfeito';
                } else {
                    // Se houver uma mistura de "Confirmado" e "Movimento Desfeito", mas sem pendentes/disputas
                    $new_notif_status = 'Confirmado'; // Ou um status como 'Parcialmente Resolvido' se for o caso
                }
            }
            
            $pdo->commit();
            $response['success'] = true;
            $response['new_notif_status'] = $new_notif_status; // Envia o status geral calculado

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = "Erro ao processar ação: " . $e->getMessage();
            // Log completo do erro, incluindo stack trace
            error_log("Erro na ação do administrador (ID: $administrador_logado_id, Notif: $notificacao_movimentacao_id, Item: $item_id): " . $e->getMessage() . "
Stack trace: " . $e->getTraceAsString());
        }
    } 
    // --- Processamento de Ações em Lote do Administrador ---
    elseif (isset($_POST['action'], $_POST['selected_notifications']) && $_POST['action'] == 'bulk_responder_usuario') {
        $selected_notificacoes = $_POST['selected_notifications']; // Array de IDs de notificacoes_movimentacao
        $admin_reply = isset($_POST['admin_reply']) ? trim($_POST['admin_reply']) : '';
        $administrador_logado_id = $_SESSION['id'];
        $data_atualizacao = date('Y-m-d H:i:s');
        
        if (empty($selected_notificacoes) || !is_array($selected_notificacoes)) {
            $response['message'] = 'Nenhuma notificação selecionada.';
            echo json_encode($response);
            exit;
        }
        
        if (empty($admin_reply)) {
            $response['message'] = 'Por favor, forneça uma resposta.';
            echo json_encode($response);
            exit;
        }
        
        $pdo->beginTransaction();
        try {
            // 1. Agrupar itens por usuário notificado
            $usuarios_notificados = [];
            foreach ($selected_notificacoes as $notif_id) {
                $notif_id = filter_var($notif_id, FILTER_VALIDATE_INT);
                if (!$notif_id) continue;
                
                // Buscar informações da notificação
                $sql_notif_info = "SELECT nm.item_id, nm.usuario_notificado_id, i.status_confirmacao as item_status FROM notificacoes_movimentacao nm JOIN itens i ON nm.item_id = i.id WHERE nm.id = ?";
                $stmt_notif_info = $pdo->prepare($sql_notif_info);
                $stmt_notif_info->execute([$notif_id]);
                $notif_data = $stmt_notif_info->fetch(PDO::FETCH_ASSOC);
                
                if (!$notif_data) continue;
                
                // Verificar se o status do item permite resposta
                $item_status = $notif_data['item_status'];
                if ($item_status !== 'Nao Confirmado' && $item_status !== 'Em Disputa' && $item_status !== 'Pendente') {
                    continue; // Pular itens que não podem ser respondidos
                }
                
                $usuario_id = $notif_data['usuario_notificado_id'];
                $item_id = $notif_data['item_id'];
                
                if (!isset($usuarios_notificados[$usuario_id])) {
                    $usuarios_notificados[$usuario_id] = [];
                }
                $usuarios_notificados[$usuario_id][] = ['notif_id' => $notif_id, 'item_id' => $item_id];
            }
            
            if (empty($usuarios_notificados)) {
                throw new Exception("Nenhum item selecionado pode ser respondido.");
            }
            
            // 2. Para cada usuário, atualizar seus itens
            foreach ($usuarios_notificados as $usuario_id => $itens) {
                foreach ($itens as $item_info) {
                    $notif_id = $item_info['notif_id'];
                    $item_id = $item_info['item_id'];
                    
                    // Atualizar notificacoes_movimentacao
                    $sql_update_notif = "UPDATE notificacoes_movimentacao SET status_confirmacao = 'Pendente', resposta_admin = ?, data_atualizacao = ? WHERE id = ?";
                    $stmt_update_notif = $pdo->prepare($sql_update_notif);
                    $stmt_update_notif->execute([$admin_reply, $data_atualizacao, $notif_id]);
                    
                    // Atualizar itens
                    $sql_update_item = "UPDATE itens SET status_confirmacao = 'Pendente' WHERE id = ?";
                    $stmt_update_item = $pdo->prepare($sql_update_item);
                    $stmt_update_item->execute([$item_id]);
                    
                    // Inserir no histórico
                    $sql_insert_history = "INSERT INTO notificacoes_respostas_historico (notificacao_movimentacao_id, remetente_id, tipo_remetente, conteudo_resposta, data_resposta) VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert_history = $pdo->prepare($sql_insert_history);
                    $stmt_insert_history->execute([$notif_id, $administrador_logado_id, 'admin', $admin_reply, $data_atualizacao]);
                }
            }
            
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = 'Respostas enviadas com sucesso para os usuários selecionados. Os itens voltaram para o status "Pendente".';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = "Erro ao processar ação em lote: " . $e->getMessage();
            error_log("Erro na ação em lote do administrador (ID: $administrador_logado_id): " . $e->getMessage() . "
Stack trace: " . $e->getTraceAsString());
        }
    }
    // --- Processamento de Ações em Lote do Administrador para Desfazer/Atribuir ---
    elseif (isset($_POST['action'], $_POST['selected_notifications']) && 
             ($_POST['action'] == 'bulk_desfazer_movimentacao' || $_POST['action'] == 'bulk_atribuir_responsavel')) {
        $selected_notificacoes = $_POST['selected_notifications']; // Array de IDs de notificacoes_movimentacao
        $action = $_POST['action'];
        $administrador_logado_id = $_SESSION['id'];
        $data_atualizacao = date('Y-m-d H:i:s');
        
        if (empty($selected_notificacoes) || !is_array($selected_notificacoes)) {
            $response['message'] = 'Nenhuma notificação selecionada.';
            echo json_encode($response);
            exit;
        }
        
        $pdo->beginTransaction();
        try {
            $mensagens = [];
            
            foreach ($selected_notificacoes as $notif_id) {
                $notif_id = filter_var($notif_id, FILTER_VALIDATE_INT);
                if (!$notif_id) continue;
                
                // Buscar informações da notificação
                $sql_notif_info = "SELECT nm.item_id, nm.movimentacao_id, i.status_confirmacao as item_status FROM notificacoes_movimentacao nm JOIN itens i ON nm.item_id = i.id WHERE nm.id = ?";
                $stmt_notif_info = $pdo->prepare($sql_notif_info);
                $stmt_notif_info->execute([$notif_id]);
                $notif_data = $stmt_notif_info->fetch(PDO::FETCH_ASSOC);
                
                if (!$notif_data) continue;
                
                // Verificar se o status do item permite ação
                $item_status = $notif_data['item_status'];
                if ($item_status !== 'Nao Confirmado' && $item_status !== 'Em Disputa' && $item_status !== 'Pendente') {
                    $mensagens[] = "Item da notificação #{$notif_id} não pode ser processado (status inválido).";
                    continue;
                }
                
                $item_id = $notif_data['item_id'];
                $movimentacao_id = $notif_data['movimentacao_id'];
                
                if ($action == 'bulk_desfazer_movimentacao') {
                    // Buscar dados da movimentação
                    $sql_get_mov_details = "SELECT local_origem_id, usuario_anterior_id FROM movimentacoes WHERE id = ?";
                    $stmt_get_mov_details = $pdo->prepare($sql_get_mov_details);
                    $stmt_get_mov_details->execute([$movimentacao_id]);
                    $mov_details = $stmt_get_mov_details->fetch(PDO::FETCH_ASSOC);
                    
                    if ($mov_details && !empty($mov_details['usuario_anterior_id'])) {
                        // Desfazer movimentação
                        $new_item_status = 'Confirmado';
                        $sql_update_item = "UPDATE itens SET local_id = ?, responsavel_id = ?, status_confirmacao = ? WHERE id = ?";
                        $stmt_update_item = $pdo->prepare($sql_update_item);
                        $stmt_update_item->execute([$mov_details['local_origem_id'], $mov_details['usuario_anterior_id'], $new_item_status, $item_id]);
                        
                        $sql_update_notif = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, data_atualizacao = ? WHERE id = ?";
                        $stmt_update_notif = $pdo->prepare($sql_update_notif);
                        $stmt_update_notif->execute([$new_item_status, $data_atualizacao, $notif_id]);
                        
                        $mensagens[] = "Movimentação do item #{$item_id} desfeita.";
                    } else {
                        $mensagens[] = "Não foi possível desfazer a movimentação do item #{$item_id}.";
                    }
                } elseif ($action == 'bulk_atribuir_responsavel') {
                    $novo_responsavel_id = isset($_POST['novo_responsavel_id']) ? (int)$_POST['novo_responsavel_id'] : 0;
                    if (!$novo_responsavel_id) {
                        throw new Exception('Selecione um novo responsável válido.');
                    }
                    
                    // Obter dados atuais do item
                    $stmt_item = $pdo->prepare('SELECT local_id, responsavel_id FROM itens WHERE id = ?');
                    $stmt_item->execute([$item_id]);
                    $item_row = $stmt_item->fetch(PDO::FETCH_ASSOC);
                    if (!$item_row) {
                        $mensagens[] = "Item #{$item_id} não encontrado.";
                        continue;
                    }
                    $local_atual = (int)$item_row['local_id'];
                    $responsavel_anterior = (int)$item_row['responsavel_id'];
                    
                    // Atualizar item
                    $stmt_upd_item = $pdo->prepare("UPDATE itens SET responsavel_id = ?, status_confirmacao = 'Pendente' WHERE id = ?");
                    $stmt_upd_item->execute([$novo_responsavel_id, $item_id]);
                    
                    // Inserir nova movimentação
                    $stmt_mov = $pdo->prepare('INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id, usuario_destino_id, data_movimentacao) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                    $stmt_mov->execute([$item_id, $local_atual, $local_atual, $administrador_logado_id, $responsavel_anterior, $novo_responsavel_id]);
                    $nova_mov_id = $pdo->lastInsertId();
                    
                    // Criar nova notificação
                    $stmt_nm = $pdo->prepare("INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')");
                    $stmt_nm->execute([$nova_mov_id, $item_id, $novo_responsavel_id]);
                    
                    // Marcar notificação antiga como 'Movimento Desfeito'
                    $stmt_close_nm = $pdo->prepare("UPDATE notificacoes_movimentacao SET status_confirmacao = 'Movimento Desfeito', data_atualizacao = ? WHERE id = ?");
                    $stmt_close_nm->execute([$data_atualizacao, $notif_id]);
                    
                    $mensagens[] = "Novo responsável atribuído ao item #{$item_id}.";
                }
            }
            
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = implode(' ', $mensagens);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = "Erro ao processar ação em lote: " . $e->getMessage();
            error_log("Erro na ação em lote do administrador (ID: $administrador_logado_id): " . $e->getMessage() . "
Stack trace: " . $e->getTraceAsString());
        }
    }
    else {
        $response['message'] = 'Dados incompletos na requisição.';
    }
    // Garante que não há saída anterior
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // Important to stop execution after AJAX response
}

// Se não for uma requisição AJAX, inclui o cabeçalho e mostra o conteúdo da página
require_once 'includes/header.php';

// Redireciona para a página de login se o usuário não estiver logado ou não for Administrador
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
} else {

// --- Lógica para exibir uma única notificação ou todas ---
$notificacao_unica_id = isset($_GET['notif_id']) ? (int)$_GET['notif_id'] : 0;
$notificacoes = []; // Este array agora conterá os registros de notificacoes_movimentacao

// Obtém o status de filtro da URL, padrão é 'Todos'
$filtro_status = isset($_GET['status']) ? $_GET['status'] : 'Todos';

// Ajustar o filtro para corresponder aos mesmos filtros da página do usuário
// Na página do usuário, 'Pendente' inclui 'Pendente' e 'Em Disputa'
// Na página do administrador, vamos seguir a mesma lógica
$filtro_status_ajustado = $filtro_status;
if ($filtro_status == 'Pendente') {
    // Para pendentes, vamos mostrar Pendente e Em Disputa
    $filtro_status_ajustado = 'Pendente'; // Vamos ajustar a query depois
} else if ($filtro_status == 'Confirmado') {
    $filtro_status_ajustado = 'Confirmado';
} else if ($filtro_status == 'Nao Confirmado') {
    // Para não confirmados, vamos mostrar apenas Nao Confirmado
    $filtro_status_ajustado = 'Nao Confirmado';
} else {
    $filtro_status_ajustado = 'Todos';
}

// SQL base para buscar notificações de movimentação

// Obter o ID do usuário "Lixeira"
$lixeira_id = null;
$stmt_lixeira = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
$stmt_lixeira->execute();
$lixeira = $stmt_lixeira->fetch(PDO::FETCH_ASSOC);
$lixeira_id = $lixeira ? $lixeira['id'] : null;
error_log("ID do usuário Lixeira: " . ($lixeira_id ? $lixeira_id : "não encontrado"));

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
    WHERE 1=1
";

$params = [];

// Adicionar condição para excluir itens da lixeira, se o ID da lixeira for conhecido
if ($lixeira_id) {
    $sql .= " AND i.responsavel_id != ?";
    $params[] = $lixeira_id;
    error_log("Adicionando condição para excluir itens da lixeira com ID: " . $lixeira_id);
} else {
    error_log("ID do usuário Lixeira não encontrado, não adicionando condição de filtro");
}

if ($notificacao_unica_id > 0) {
    $sql .= " AND nm.id = ?";
    $params[] = $notificacao_unica_id;
    error_log("Adicionando filtro por ID de notificação: " . $notificacao_unica_id);
} else {
    // Filtra pelo status do item, não da notificação
    if ($filtro_status != 'Todos') {
        if ($filtro_status == 'Pendente') {
            // Para Pendente, incluir também 'Em Disputa'
            $sql .= " AND (i.status_confirmacao = ? OR i.status_confirmacao = ?)";
            $params[] = 'Pendente';
            $params[] = 'Em Disputa';
            error_log("Adicionando filtro por status Pendente e Em Disputa");
        } else if ($filtro_status == 'Confirmado') {
            $sql .= " AND i.status_confirmacao = ?";
            $params[] = 'Confirmado';
            error_log("Adicionando filtro por status Confirmado");
        } else if ($filtro_status == 'Nao Confirmado') {
            $sql .= " AND i.status_confirmacao = ?";
            $params[] = 'Nao Confirmado';
            error_log("Adicionando filtro por status Não Confirmado");
        }
    }
}

$sql .= " ORDER BY nm.data_notificacao DESC";
error_log("SQL final: " . $sql);
error_log("Parâmetros finais: " . print_r($params, true));

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notificacoes_movimentacao_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$usuarios_aprovados = $pdo->query("SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Reestrutura os dados para compatibilidade com o HTML existente
$notificacoes = [];
foreach ($notificacoes_movimentacao_raw as $nm) {
    $notificacoes[] = [
        'id' => $nm['id'],
        'tipo' => 'transferencia',
        'mensagem' => "Movimentação do item: " . htmlspecialchars($nm['item_nome']) . " (Patrimônio: " . htmlspecialchars($nm['patrimonio_novo']) . "). Status: " . htmlspecialchars($nm['item_status']),
        'status' => $nm['item_status'], // Status do item
        'data_envio' => $nm['data_notificacao'],
        'usuario_nome' => $nm['usuario_notificado_nome'],
        'administrador_nome' => $nm['admin_nome'],
        'assunto_titulo' => 'Movimentação de Item',
        'assunto_resumo' => "Item: " . htmlspecialchars($nm['item_nome']) . " - Status: " . htmlspecialchars($nm['item_status']),
        'justificativa' => $nm['justificativa_usuario'],
        'data_resposta' => $nm['data_atualizacao'],
        'admin_reply' => $nm['resposta_admin'],
        'admin_reply_date' => $nm['data_atualizacao'],
        'detalhes_itens' => [
            [
                'id' => $nm['item_id'],
                'status_confirmacao' => $nm['item_status'],
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
        ]
    ];
}

?>

<style>
.notification-item {
    position: relative;
}

.bulk-select-checkbox {
    width: 18px;
    height: 18px;
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    cursor: pointer;
}

.card-header.notification-summary {
    cursor: pointer;
    padding-left: 30px;
}

.notification-details {
    padding-left: 30px;
}
</style>

<div class="container mt-5">
    <?php if ($notificacao_unica_id > 0): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Detalhes da Notificação <i class="fas fa-shield-alt" style="color: #124a80;"></i></h2>
            <a href="notificacoes_admin.php" class="btn btn-primary btn-sm">Voltar para Notificações</a>
        </div>
    <?php else: ?>
        <!-- Sistema de abas para diferentes tipos de notificações -->
        <ul class="nav nav-tabs" id="notificacoesAdminTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="inventario-admin-tab" data-toggle="tab" href="#inventario-admin" role="tab" aria-controls="inventario-admin" aria-selected="true">Notificações de Inventário</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="almoxarifado-admin-tab" data-toggle="tab" href="#almoxarifado-admin" role="tab" aria-controls="almoxarifado-admin" aria-selected="false">Almoxarifado</a>
            </li>
        </ul>
        
        <div class="tab-content" id="notificacoesAdminTabContent">
            <!-- Aba de Notificações de Inventário -->
            <div class="tab-pane fade show active" id="inventario-admin" role="tabpanel" aria-labelledby="inventario-admin-tab">
                <h2 class="mt-3">Gerenciar Notificações de Inventário <i class="fas fa-shield-alt" style="color: #124a80;"></i></h2>
                <p>Visualize o status das notificações de movimentação e as justificativas dos usuários.</p>

                <div class="mb-3">
                    <label for="filtroStatus">Filtrar por Status:</label>
                    <select id="filtroStatus" class="form-control" onchange="window.location.href='notificacoes_admin.php?status=' + this.value">
                        <option value="Todos" <?php echo ($filtro_status == 'Todos') ? 'selected' : ''; ?>>Todos</option>
                        <option value="Pendente" <?php echo ($filtro_status == 'Pendente') ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="Confirmado" <?php echo ($filtro_status == 'Confirmado') ? 'selected' : ''; ?>>Confirmados</option>
                        <option value="Nao Confirmado" <?php echo ($filtro_status == 'Nao Confirmado') ? 'selected' : ''; ?>>Não Confirmados</option>
                    </select>
                </div>
                
                <!-- Botões de Ação em Lote -->
                <div id="bulk-action-buttons" class="mb-3" style="display: none;">
                    <button id="bulkResponderBtn" class="btn btn-primary btn-sm">
                        <i class="fas fa-reply"></i> Responder ao Usuário
                    </button>
                    <button id="bulkDesfazerBtn" class="btn btn-warning btn-sm">
                        <i class="fas fa-undo"></i> Desfazer Movimentação
                    </button>
                    <button id="bulkAtribuirBtn" class="btn btn-info btn-sm">
                        <i class="fas fa-user-plus"></i> Escolher Novo Responsável
                    </button>
                </div>
                
                <!-- Formulário de Resposta em Lote -->
                <div id="bulk-resposta-form" class="card mt-3" style="display: none;">
                    <div class="card-header">
                        <h5>Responder aos Usuários Selecionados</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="bulk-admin-reply">Sua Resposta:</label>
                            <textarea id="bulk-admin-reply" class="form-control" rows="3" placeholder="Informe a justificativa..."></textarea>
                        </div>
                        <button id="submitBulkReply" class="btn btn-success">Enviar Respostas</button>
                        <button id="cancelBulkReply" class="btn btn-secondary">Cancelar</button>
                    </div>
                </div>
                
                <!-- Formulário de Atribuição em Lote -->
                <div id="bulk-atribuir-form" class="card mt-3" style="display: none;">
                    <div class="card-header">
                        <h5>Escolher Novo Responsável para Itens Selecionados</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="bulk-novo-responsavel">Selecione o novo responsável:</label>
                            <select id="bulk-novo-responsavel" class="form-control">
                                <option value="">Selecione...</option>
                                <?php foreach ($usuarios_aprovados as $usr): ?>
                                    <option value="<?php echo $usr['id']; ?>"><?php echo htmlspecialchars($usr['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button id="submitBulkAtribuir" class="btn btn-success">Atribuir e Notificar</button>
                        <button id="cancelBulkAtribuir" class="btn btn-secondary">Cancelar</button>
                    </div>
                </div>
                
                <div id="feedback-message" class="alert" style="display:none;"></div>

                <?php if (empty($notificacoes)): ?>
                    <div class="alert alert-info">Nenhuma notificação encontrada com o filtro selecionado.</div>
                <?php else: ?>
                    <div class="notification-inbox">
                        <?php foreach ($notificacoes as $notificacao): ?>
                            <div class="notification-item card mb-2" data-notif-id="<?php echo $notificacao['id']; ?>" style="position: relative;">
                                <?php if ($notificacao['status'] == 'Nao Confirmado' || $notificacao['status'] == 'Em Disputa'): ?>
                                    <input type="checkbox" class="bulk-select-checkbox mr-2" data-notif-id="<?php echo $notificacao['id']; ?>" style="width: 18px; height: 18px; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); z-index: 10;">
                                <?php endif; ?>
                                <div class="card-header notification-summary" style="cursor: pointer; padding-left: 30px;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas <?php 
                                                if ($notificacao['status'] == 'Pendente' || $notificacao['status'] == 'Em Disputa') echo 'fa-envelope';
                                                else if ($notificacao['status'] == 'Confirmado' || $notificacao['status'] == 'Movimento Desfeito') echo 'fa-envelope-open';
                                                else echo 'fa-envelope'; // Fallback
                                            ?>"></i>
                                            <strong><?php echo htmlspecialchars($notificacao['usuario_nome']); ?></strong>
                                            <strong class="ml-2"><?php echo $notificacao['assunto_titulo']; ?>:</strong>
                                            <span class="assunto-resumo"><?php echo htmlspecialchars($notificacao['assunto_resumo']); ?></span>
                                        </div>
                                        <div>
                                            <span class="badge badge-<?php 
                                                if($notificacao['status'] == 'Pendente') echo 'warning';
                                                else if($notificacao['status'] == 'Confirmado') echo 'success';
                                                else if($notificacao['status'] == 'Nao Confirmado') echo 'danger';
                                                else if($notificacao['status'] == 'Em Disputa') echo 'danger'; // Disputa em vermelho
                                                else if($notificacao['status'] == 'Movimento Desfeito') echo 'info'; // Movimento desfeito em azul
                                                else echo 'secondary'; // Fallback
                                            ?>">
                                                <?php echo htmlspecialchars($notificacao['status']); ?>
                                            </span>
                                            <small class="text-muted ml-2"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_envio'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body notification-details" <?php echo ($notificacao_unica_id > 0) ? '' : 'style="display: none;"'; ?>>

                                    <p><strong>Mensagem Geral:</strong> <?php echo nl2br(htmlspecialchars($notificacao['mensagem'])); ?></p>

                                    <!-- Histórico de Conversa -->
                                    <?php
                                    // Buscar histórico de respostas para esta notificação
                                    $sql_historico = "SELECT * FROM notificacoes_respostas_historico WHERE notificacao_movimentacao_id = ? ORDER BY data_resposta ASC";
                                    $stmt_historico = $pdo->prepare($sql_historico);
                                    $stmt_historico->execute([$notificacao['id']]);
                                    $historico_respostas = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="card mt-3 mb-3">
                                        <div class="card-header"><strong>Histórico da Conversa</strong></div>
                                        <div class="card-body" style="max-height:350px; overflow-y:auto; background:#f8f9fa;">
                                            <style>
                                            .chat-bubble { max-width: 70%; padding: 10px 15px; border-radius: 15px; margin-bottom: 8px; position: relative; }
                                            .chat-admin { background: #e3f0fa; color: #124a80; align-self: flex-end; margin-left:auto; }
                                            .chat-user { background: #e9ecef; color: #333; align-self: flex-start; margin-right:auto; }
                                            .chat-meta { font-size: 0.85em; color: #888; margin-bottom: 2px; }
                                            .chat-container { display: flex; flex-direction: column; }
                                            </style>
                                            <div class="chat-container">
                                            <?php if (empty($historico_respostas)): ?>
                                                <div class="text-muted">Nenhuma mensagem registrada ainda.</div>
                                            <?php else: ?>
                                                <?php foreach ($historico_respostas as $msg): ?>
                                                    <div class="chat-bubble chat-<?php echo $msg['tipo_remetente'] === 'admin' ? 'admin' : 'user'; ?>">
                                                        <div class="chat-meta">
                                                            <strong><?php echo $msg['tipo_remetente'] === 'admin' ? 'Administrador' : 'Usuário'; ?></strong>
                                                            &bull; <?php echo date('d/m/Y H:i', strtotime($msg['data_resposta'])); ?>
                                                        </div>
                                                        <div><?php echo nl2br(htmlspecialchars($msg['conteudo_resposta'])); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="mt-4">Detalhes dos Itens Associados:</h6>
                                    <?php if (!empty($notificacao['detalhes_itens'])): ?>
                                        <?php foreach ($notificacao['detalhes_itens'] as $item): ?>
                                            <div class="item-detail-card card mb-2" data-item-id="<?php echo $item['id']; ?>">
                                                <div class="card-body">
                                                    <h7 class="card-title">Item: <?php echo htmlspecialchars($item['nome']); ?> (Patrimônio: <?php echo htmlspecialchars($item['patrimonio_novo']); ?>)</h7>
                                                    <ul>
                                                        <li><strong>ID:</strong> <?php echo htmlspecialchars($item['id']); ?></li>
                                                        <li><strong>Patrimônio Secundário:</strong> <?php echo htmlspecialchars($item['patrimonio_secundario']); ?></li>
                                                        <li><strong>Local:</strong> <?php echo htmlspecialchars($item['local_nome']); ?></li>
                                                        <li><strong>Responsável Atual:</strong> <?php echo htmlspecialchars($item['responsavel_nome']); ?></li>
                                                        <li><strong>Estado:</strong> <?php echo nl2br(htmlspecialchars($item['observacao'])); ?></li>
                                                        <li><strong>Status Confirmação:</strong> 
                                                            <span class="badge badge-<?php 
                                                                if($item['status_confirmacao'] == 'Pendente') echo 'warning';
                                                                else if($item['status_confirmacao'] == 'Confirmado') echo 'success';
                                                                else echo 'danger';
                                                            ?>">
                                                                <?php echo htmlspecialchars($item['status_confirmacao']); ?>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                    <?php if (!empty($item['admin_reply'])): ?>
                                                        <div class="alert alert-info mt-2">
                                                            <strong>Sua Resposta Anterior (Item):</strong> <?php echo nl2br(htmlspecialchars($item['admin_reply'])); ?><br>
                                                            <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($item['data_admin_reply'])); ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($item['status_confirmacao'] == 'Nao Confirmado' || $item['status_confirmacao'] == 'Em Disputa'): ?>
                                                        <div class="admin-item-actions mt-2">
                                                            <?php if (empty($item['usuario_anterior_id'])): ?>
                                                                <button type="button" class="btn btn-warning btn-sm" onclick="toggleAssignForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Escolher outro responsável</button>
                                                                <div id="admin_assign_form_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" style="display:none; margin-top:10px;">
                                                                    <form class="admin-item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                                        <input type="hidden" name="action" value="atribuir_novo_responsavel">
                                                                        <div class="form-group">
                                                                            <label>Selecione o novo responsável:</label>
                                                                            <select name="novo_responsavel_id" class="form-control" required>
                                                                                <option value="">Selecione...</option>
                                                                                <?php foreach ($usuarios_aprovados as $usr): ?>
                                                                                    <option value="<?php echo $usr['id']; ?>"><?php echo htmlspecialchars($usr['nome']); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <button type="submit" class="btn btn-success btn-sm">Enviar notificação</button>
                                                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAssignForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Cancelar</button>
                                                                    </form>
                                                                </div>
                                                            <?php else: ?>
                                                                <form class="d-inline-block admin-item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                                    <input type="hidden" name="action" value="desfazer_movimentacao_item">
                                                                    <button type="submit" class="btn btn-danger btn-sm">Desfazer Movimentação</button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-primary btn-sm ml-2" onclick="toggleAdminItemReplyForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Responder</button>
                                                            <div id="admin_item_reply_form_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" style="display:none; margin-top: 10px;">
                                                                <form class="admin-item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                                    <input type="hidden" name="action" value="responder_item_disputa">
                                                                    <div class="form-group">
                                                                        <label for="admin_item_reply_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>">Sua Resposta para este item:</label>
                                                                        <textarea name="admin_reply" id="admin_item_reply_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" class="form-control" rows="2" required><?php echo htmlspecialchars($item['admin_reply'] ?? ''); ?></textarea>
                                                                    </div>
                                                                    <button type="submit" class="btn btn-success btn-sm">Enviar Resposta</button>
                                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAdminItemReplyForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Cancelar</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>Nenhum item associado a esta notificação.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Aba de Notificações do Almoxarifado -->
            <div class="tab-pane fade" id="almoxarifado-admin" role="tabpanel" aria-labelledby="almoxarifado-admin-tab">
                <h2 class="mt-3">Gerenciar Notificações do Almoxarifado</h2>
                <p>Visualize as confirmações e justificativas dos usuários para os itens de almoxarifado.</p>
                
                <?php
                // Buscar notificações do almoxarifado
                $sql_almoxarifado = "
                    SELECT
                        n.id as notificacao_id,
                        n.mensagem,
                        n.status as notificacao_status,
                        n.data_criacao,
                        am.id as item_id,
                        am.nome as item_nome,
                        am.status_confirmacao as item_status_confirmacao,
                        u.nome as usuario_nome,
                        nar.justificativa,
                        nar.data_resposta
                    FROM notificacoes n
                    JOIN notificacoes_almoxarifado_detalhes nad ON n.id = nad.notificacao_id
                    JOIN almoxarifado_materiais am ON nad.item_id = am.id
                    JOIN usuarios u ON n.usuario_id = u.id
                    LEFT JOIN notificacoes_almoxarifado_respostas nar ON n.id = nar.notificacao_id AND am.id = nar.item_id
                    WHERE n.tipo = 'atribuicao_almoxarifado'
                    ORDER BY n.data_criacao DESC
                ";

                $stmt_almoxarifado = $pdo->query($sql_almoxarifado);
                $notificacoes_almoxarifado = $stmt_almoxarifado->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (empty($notificacoes_almoxarifado)): ?>
                    <div class="alert alert-info">Nenhuma notificação do almoxarifado encontrada.</div>
                <?php else: ?>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID Not.</th>
                                <th>Usuário</th>
                                <th>Item</th>
                                <th>Status</th>
                                <th>Justificativa</th>
                                <th>Data Notificação</th>
                                <th>Data Resposta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notificacoes_almoxarifado as $notificacao): ?>
                                <tr>
                                    <td><?php echo $notificacao['notificacao_id']; ?></td>
                                    <td><?php echo htmlspecialchars($notificacao['usuario_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($notificacao['item_nome']); ?></td>
                                    <td><span class="badge <?php 
                                        if($notificacao['item_status_confirmacao'] == 'Pendente') echo 'badge-warning';
                                        else if($notificacao['item_status_confirmacao'] == 'Confirmado') echo 'badge-success';
                                        else echo 'badge-danger';
                                    ?>"><?php echo htmlspecialchars($notificacao['item_status_confirmacao']); ?></span></td>
                                    <td><?php echo htmlspecialchars($notificacao['justificativa']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></td>
                                    <td><?php echo $notificacao['data_resposta'] ? date('d/m/Y H:i', strtotime($notificacao['data_resposta'])) : ''; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Funções para exibir/esconder o formulário de resposta do administrador
function toggleAdminReplyForm(notifId) {
    const form = document.getElementById('admin_reply_form_' + notifId);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}

// Função para exibir/esconder o formulário de resposta do administrador para ITENS
function toggleAdminItemReplyForm(notifId, itemId) {
    const form = document.getElementById(`admin_item_reply_form_${notifId}_${itemId}`);
    if (form) {
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
}

function toggleAssignForm(notifId, itemId) {
    const form = document.getElementById(`admin_assign_form_${notifId}_${itemId}`);
    if (form) {
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
}

// --- Funções para Ações em Lote ---
function showBulkActionButtons() {
    const bulkActionButtons = document.getElementById('bulk-action-buttons');
    const checkboxes = document.querySelectorAll('.bulk-select-checkbox:checked');
    
    if (checkboxes.length > 0) {
        bulkActionButtons.style.display = 'block';
    } else {
        bulkActionButtons.style.display = 'none';
        // Esconder formulários se nenhum item estiver selecionado
        document.getElementById('bulk-resposta-form').style.display = 'none';
        document.getElementById('bulk-atribuir-form').style.display = 'none';
    }
}

function hideBulkForms() {
    document.getElementById('bulk-resposta-form').style.display = 'none';
    document.getElementById('bulk-atribuir-form').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    // --- Lógica para cliques no card ---
    document.querySelectorAll('.notification-item').forEach(item => {
        const notifId = item.dataset.notifId;
        const cardHeader = item.querySelector('.card-header');
        const checkbox = item.querySelector('.bulk-select-checkbox');
        
        if (cardHeader) {
            cardHeader.addEventListener('click', function(e) {
                // Verificar se o clique foi no checkbox ou em seus elementos filhos
                if (e.target === checkbox || (checkbox && checkbox.contains(e.target))) {
                    // Não fazer nada, deixar o evento do checkbox lidar com isso
                    return;
                }
                // Redirecionar para a página de detalhes
                window.location.href = 'notificacoes_admin.php?notif_id=' + notifId;
            });
        }
    });
    
    // --- Lógica para Ações Individuais ---
    document.querySelectorAll('.admin-item-action-form').forEach(form => { // Changed selector to target item-specific forms
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Previne o recarregamento da página

            const formData = new FormData(this);
            formData.append('is_ajax', 'true'); // Indica que é uma requisição AJAX
            formData.append('notificacao_id', this.dataset.notifId);
            formData.append('item_id', this.dataset.itemId); // Adicionado item_id

            const action = formData.get('action');
            const notifId = this.dataset.notifId;
            const itemId = this.dataset.itemId; // Captura itemId

            fetch('notificacoes_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;

                if (data.success) {
                    feedbackMessage.className = 'alert alert-success';

                    // Atualiza o status do item específico na UI
                    const itemCard = document.querySelector(`.item-detail-card[data-item-id="${itemId}"]`);
                    if (itemCard) {
                        const statusBadge = itemCard.querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = data.new_item_status;
                            updateBadgeClass(statusBadge, data.new_item_status); // Reutiliza função de update de badge
                        }
                        const actionContainer = itemCard.querySelector('.admin-item-actions');
                        if (actionContainer) {
                            actionContainer.style.display = 'none'; // Esconde os botões de ação do item
                        }
                        // Esconde o formulário de resposta do admin para este item
                        if (action === 'responder_item_disputa') {
                            // No longer calling toggleAdminItemReplyForm here as it's handled by the button click
                        }
                    }

                    // Atualiza o status geral da notificação na UI
                    const notifItem = document.querySelector(`.notification-item[data-notif-id="${notifId}"]`);
                    if (notifItem) {
                        const notifStatusBadge = notifItem.querySelector('.badge'); // Seletor mais específico para o badge geral
                        const notifIcon = notifItem.querySelector('.fas');

                        if (notifStatusBadge && data.new_notif_status) { // Verifica se new_notif_status foi enviado
                            notifStatusBadge.textContent = data.new_notif_status;
                            updateBadgeClass(notifStatusBadge, data.new_notif_status); // Reutiliza função de update de badge
                        }
                        if (notifIcon && data.new_notif_status) {
                            updateNotifIcon(notifIcon, data.new_notif_status); // Reutiliza função de update de ícone
                        }
                    }

                } else {
                    feedbackMessage.className = 'alert alert-danger';
                }
            })
            .catch(error => {
                console.error('Erro na requisição Fetch:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = 'alert alert-danger';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
            });
        });
    });
    
    // --- Lógica para Ações em Lote ---
    // Mostrar/ocultar botões de ação em lote ao selecionar checkboxes
    document.querySelectorAll('.bulk-select-checkbox').forEach(checkbox => {
        // Adicionar listener de mudança
        checkbox.addEventListener('change', function(e) {
            showBulkActionButtons();
        });
        
        // Adicionar listener de clique para impedir a propagação
        checkbox.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Botão de Responder ao Usuário em Lote
    document.getElementById('bulkResponderBtn').addEventListener('click', function() {
        hideBulkForms();
        document.getElementById('bulk-resposta-form').style.display = 'block';
    });
    
    // Botão de Desfazer Movimentação em Lote
    document.getElementById('bulkDesfazerBtn').addEventListener('click', function() {
        const selectedNotifIds = Array.from(document.querySelectorAll('.bulk-select-checkbox:checked'))
                                     .map(cb => cb.dataset.notifId);
        
        if (selectedNotifIds.length === 0) {
            alert('Nenhuma notificação selecionada.');
            return;
        }
        
        if (confirm(`Tem certeza que deseja desfazer a movimentação de ${selectedNotifIds.length} item(s)?`)) {
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('action', 'bulk_desfazer_movimentacao');
            selectedNotifIds.forEach(id => formData.append('selected_notifications[]', id));
            
            fetch('notificacoes_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                
                if (data.success) {
                    // Atualizar a página ou os elementos afetados
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erro na requisição Fetch:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = 'alert alert-danger';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
            });
        }
    });
    
    // Botão de Escolher Novo Responsável em Lote
    document.getElementById('bulkAtribuirBtn').addEventListener('click', function() {
        hideBulkForms();
        document.getElementById('bulk-atribuir-form').style.display = 'block';
    });
    
    // Submeter resposta em lote
    document.getElementById('submitBulkReply').addEventListener('click', function() {
        const replyText = document.getElementById('bulk-admin-reply').value.trim();
        const selectedNotifIds = Array.from(document.querySelectorAll('.bulk-select-checkbox:checked'))
                                     .map(cb => cb.dataset.notifId);
        
        if (selectedNotifIds.length === 0) {
            alert('Nenhuma notificação selecionada.');
            return;
        }
        
        if (replyText === '') {
            alert('Por favor, informe uma resposta.');
            return;
        }
        
        const formData = new FormData();
        formData.append('is_ajax', 'true');
        formData.append('action', 'bulk_responder_usuario');
        formData.append('admin_reply', replyText);
        selectedNotifIds.forEach(id => formData.append('selected_notifications[]', id));
        
        fetch('notificacoes_admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const feedbackMessage = document.getElementById('feedback-message');
            feedbackMessage.style.display = 'block';
            feedbackMessage.textContent = data.message;
            feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
            
            if (data.success) {
                // Limpar formulário e checkboxes
                document.getElementById('bulk-admin-reply').value = '';
                document.querySelectorAll('.bulk-select-checkbox:checked').forEach(cb => cb.checked = false);
                hideBulkForms();
                showBulkActionButtons();
                // Atualizar a página ou os elementos afetados
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erro na requisição Fetch:', error);
            const feedbackMessage = document.getElementById('feedback-message');
            feedbackMessage.style.display = 'block';
            feedbackMessage.className = 'alert alert-danger';
            feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
        });
    });
    
    // Submeter atribuição em lote
    document.getElementById('submitBulkAtribuir').addEventListener('click', function() {
        const novoResponsavelId = document.getElementById('bulk-novo-responsavel').value;
        const selectedNotifIds = Array.from(document.querySelectorAll('.bulk-select-checkbox:checked'))
                                     .map(cb => cb.dataset.notifId);
        
        if (selectedNotifIds.length === 0) {
            alert('Nenhuma notificação selecionada.');
            return;
        }
        
        if (novoResponsavelId === '') {
            alert('Por favor, selecione um novo responsável.');
            return;
        }
        
        const formData = new FormData();
        formData.append('is_ajax', 'true');
        formData.append('action', 'bulk_atribuir_responsavel');
        formData.append('novo_responsavel_id', novoResponsavelId);
        selectedNotifIds.forEach(id => formData.append('selected_notifications[]', id));
        
        fetch('notificacoes_admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const feedbackMessage = document.getElementById('feedback-message');
            feedbackMessage.style.display = 'block';
            feedbackMessage.textContent = data.message;
            feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
            
            if (data.success) {
                // Limpar formulário e checkboxes
                document.getElementById('bulk-novo-responsavel').value = '';
                document.querySelectorAll('.bulk-select-checkbox:checked').forEach(cb => cb.checked = false);
                hideBulkForms();
                showBulkActionButtons();
                // Atualizar a página ou os elementos afetados
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erro na requisição Fetch:', error);
            const feedbackMessage = document.getElementById('feedback-message');
            feedbackMessage.style.display = 'block';
            feedbackMessage.className = 'alert alert-danger';
            feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
        });
    });
    
    // Cancelar resposta em lote
    document.getElementById('cancelBulkReply').addEventListener('click', function() {
        document.getElementById('bulk-resposta-form').style.display = 'none';
        document.getElementById('bulk-admin-reply').value = '';
    });
    
    // Cancelar atribuição em lote
    document.getElementById('cancelBulkAtribuir').addEventListener('click', function() {
        document.getElementById('bulk-atribuir-form').style.display = 'none';
        document.getElementById('bulk-novo-responsavel').value = '';
    });
    
    // Funções auxiliares para atualizar a classe do badge e o ícone (copiadas de notificacoes_usuario.php)
    function updateBadgeClass(badge, status) {
        badge.className = 'badge '; // Reseta classes
        if (status === 'Pendente') badge.classList.add('badge-warning');
        else if (status === 'Confirmado') badge.classList.add('badge-success');
        else if (status === 'Nao Confirmado' || status === 'Em Disputa') badge.classList.add('badge-danger');
        else if (status === 'Movimento Desfeito') badge.classList.add('badge-info');
        else badge.classList.add('badge-secondary');
    }

    function updateNotifIcon(icon, status) {
        icon.className = 'fas'; // Reseta classes
        if (status === 'Pendente' || status === 'Em Disputa') icon.classList.add('fa-envelope');
        else if (status === 'Confirmado' || status === 'Movimento Desfeito') icon.classList.add('fa-envelope-open');
        else icon.classList.add('fa-envelope');
    }
    
    // --- Lógica para o Filtro de Status (similar à página do usuário) ---
    const filtroStatus = document.getElementById('filtroStatus');
    if (filtroStatus) {
        filtroStatus.addEventListener('change', function() {
            window.location.href = 'notificacoes_admin.php?status=' + this.value;
        });
    }
});
</script>

<?php
    require_once 'includes/footer.php';
} // Fim do bloco if (!is_ajax)
?>

<?php
/**
 * Script da API para movimentar múltiplos itens do inventário.
 * Recebe uma lista de IDs de itens, um novo local e um novo responsável,
 * e realiza as seguintes ações em uma transação de banco de dados:
 * 1. Busca o local de origem do item.
 * 2. Atualiza o local, o responsável e o status de confirmação de cada item.
 * 3. Registra a movimentação na tabela `movimentacoes` para cada item.
 * 4. Cria uma notificação individual na tabela `notificacoes_movimentacao` para cada item movimentado.
 */

// Requer o arquivo de configuração do banco de dados (que já inicia a sessão)
require_once '../config/db.php';

// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json');

// --- Validação de Permissão ---
// Verifica se o usuário está logado e se tem permissão de Administrador
if (!isset($_SESSION['id']) || $_SESSION['permissao'] != 'Administrador') {
    // Se não for, retorna um erro e encerra o script
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

// --- Obtenção dos Dados de Entrada ---
// Pega os dados JSON enviados no corpo da requisição
$data = json_decode(file_get_contents('php://input'), true);

// Extrai os dados para variáveis, com valores padrão para evitar erros
$item_ids = $data['item_ids'] ?? [];
$novo_local_id = $data['novo_local_id'] ?? null;
$novo_responsavel_id = $data['novo_responsavel_id'] ?? null;
$administrador_id = $_SESSION['id']; // ID do admin que está fazendo a operação

// --- Validação dos Dados ---
// Verifica se todos os dados necessários foram fornecidos
if (empty($item_ids) || empty($novo_local_id) || empty($novo_responsavel_id)) {
    // Se faltar algum dado, retorna um erro
    echo json_encode(['success' => false, 'message' => 'Dados insuficientes para realizar a movimentação.']);
    exit;
}

// --- Verificações de Segurança ---
// Verificar se os itens podem ser movimentados
foreach ($item_ids as $item_id) {
    // 1. Verificar se o item está com status "Confirmado"
    $sql_check_status = "SELECT status_confirmacao FROM itens WHERE id = ?";
    $stmt_check_status = $pdo->prepare($sql_check_status);
    $stmt_check_status->execute([$item_id]);
    $item_status = $stmt_check_status->fetch(PDO::FETCH_ASSOC);
    
    if (!$item_status) {
        echo json_encode(['success' => false, 'message' => "Item com ID {$item_id} não encontrado."]);
        exit;
    }
    
    if ($item_status['status_confirmacao'] !== 'Confirmado') {
        echo json_encode(['success' => false, 'message' => "O item com ID {$item_id} não pode ser movimentado porque seu status não é 'Confirmado'. Status atual: '{$item_status['status_confirmacao']}'"]);
        exit;
    }
    
    // 2. Verificar se o item já possui uma solicitação de movimentação pendente
    $sql_check_pending = "SELECT COUNT(*) FROM notificacoes_movimentacao WHERE item_id = ? AND status_confirmacao = 'Pendente'";
    $stmt_check_pending = $pdo->prepare($sql_check_pending);
    $stmt_check_pending->execute([$item_id]);
    $pending_count = $stmt_check_pending->fetchColumn();
    
    if ($pending_count > 0) {
        echo json_encode(['success' => false, 'message' => "Este item não pode ser movimentado porque já se encontra pendente de confirmação de outro usuário."]);
        exit;
    }
}

// --- Lógica da Transação ---
// Inicia uma transação para garantir que todas as operações sejam executadas com sucesso
$pdo->beginTransaction();

try {
    // Prepara a query para buscar o local de origem do item
    $sql_get_origin = "SELECT local_id, responsavel_id FROM itens WHERE id = ?"; // Adicionado responsavel_id
    $stmt_get_origin = $pdo->prepare($sql_get_origin);

    // Prepara a query para atualizar o local, responsável e status de cada item
    $sql_update_item = "UPDATE itens SET local_id = ?, responsavel_id = ?, status_confirmacao = 'Pendente' WHERE id = ?";
    $stmt_update_item = $pdo->prepare($sql_update_item);

    // Prepara a query para inserir um registro na tabela de movimentações
    $sql_insert_mov = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id, data_movimentacao) VALUES (?, ?, ?, ?, ?, NOW())"; // Adicionado usuario_anterior_id
    $stmt_insert_mov = $pdo->prepare($sql_insert_mov);

    // Prepara a query para inserir a notificação individual na tabela notificacoes_movimentacao
    $sql_insert_notif_mov = "INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')";
    $stmt_insert_notif_mov = $pdo->prepare($sql_insert_notif_mov);

    // Itera sobre cada ID de item recebido
    foreach ($item_ids as $item_id) {
        // 1. BUSCAR LOCAL DE ORIGEM E RESPONSAVEL ANTERIOR
        $stmt_get_origin->execute([$item_id]);
        $origin_data = $stmt_get_origin->fetch(PDO::FETCH_ASSOC);
        $local_origem_id = $origin_data['local_id'];
        $usuario_anterior_id = $origin_data['responsavel_id'];

        // 2. ATUALIZAR O ITEM
        $stmt_update_item->execute([$novo_local_id, $novo_responsavel_id, $item_id]);

        // 3. REGISTRAR A MOVIMENTAÇÃO
        $stmt_insert_mov->execute([$item_id, $local_origem_id, $novo_local_id, $administrador_id, $usuario_anterior_id]); // Passando usuario_anterior_id
        // Obtém o ID da movimentação recém-inserida para este item
        $movimentacao_id = $pdo->lastInsertId();

        // 4. CRIAR A NOTIFICAÇÃO INDIVIDUAL NA TABELA NOTIFICACOES_MOVIMENTACAO
        $stmt_insert_notif_mov->execute([$movimentacao_id, $item_id, $novo_responsavel_id]); // Removido 'Pendente' daqui
    }

    // --- Finalização da Transação ---
    // Se todas as operações foram bem-sucedidas, confirma a transação
    $pdo->commit();
    // Retorna uma mensagem de sucesso
    echo json_encode(['success' => true, 'message' => 'Itens movimentados e notificações enviadas com sucesso!']);

} catch (Exception $e) {
    // --- Tratamento de Erro ---
    // Se qualquer operação falhar, desfaz a transação
    $pdo->rollBack();
    // Retorna uma mensagem de erro com os detalhes da exceção
    echo json_encode(['success' => false, 'message' => 'Erro ao processar a movimentação: ' . $e->getMessage()]);
}
?>
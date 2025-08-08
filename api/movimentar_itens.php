<?php
/**
 * Script da API para movimentar múltiplos itens do inventário.
 * Recebe uma lista de IDs de itens, um novo local e um novo responsável,
 * e realiza as seguintes ações em uma transação de banco de dados:
 * 1. Busca o local de origem do item.
 * 2. Atualiza o local, o responsável e o status de confirmação de cada item.
 * 3. Registra a movimentação na tabela `movimentacoes` para cada item.
 * 4. Cria uma única notificação para o novo responsável sobre os itens recebidos.
 * Apenas administradores podem executar este script.
 */

// Requer o arquivo de configuração do banco de dados e inicia a sessão
require_once '../config/db.php';
session_start();

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

// --- Lógica da Transação ---
// Inicia uma transação para garantir que todas as operações sejam executadas com sucesso
$pdo->beginTransaction();

try {
    // Prepara a query para buscar o local de origem do item
    $sql_get_origin = "SELECT local_id FROM itens WHERE id = ?";
    $stmt_get_origin = $pdo->prepare($sql_get_origin);

    // Prepara a query para atualizar o local, responsável e status de cada item
    $sql_update_item = "UPDATE itens SET local_id = ?, responsavel_id = ?, status_confirmacao = 'Pendente' WHERE id = ?";
    $stmt_update_item = $pdo->prepare($sql_update_item);

    // Prepara a query para inserir um registro na tabela de movimentações
    $sql_insert_mov = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, data_movimentacao) VALUES (?, ?, ?, ?, NOW())";
    $stmt_insert_mov = $pdo->prepare($sql_insert_mov);

    // Itera sobre cada ID de item recebido
    foreach ($item_ids as $item_id) {
        // 1. BUSCAR LOCAL DE ORIGEM
        $stmt_get_origin->execute([$item_id]);
        $local_origem_id = $stmt_get_origin->fetchColumn();

        // 2. ATUALIZAR O ITEM
        $stmt_update_item->execute([$novo_local_id, $novo_responsavel_id, $item_id]);

        // 3. REGISTRAR A MOVIMENTAÇÃO
        $stmt_insert_mov->execute([$item_id, $local_origem_id, $novo_local_id, $novo_responsavel_id]);
    }

    // 4. CRIAR A NOTIFICAÇÃO
    // Junta os IDs dos itens em uma string separada por vírgulas para armazenar na notificação
    $itens_ids_string = implode(',', $item_ids);
    // Cria a mensagem da notificação
    $mensagem_notificacao = "Você recebeu " . count($item_ids) . " item(ns) para sua responsabilidade. Por favor, confirme o recebimento.";
    
    // Prepara a query para inserir a notificação para o novo responsável
    $sql_notificacao = "INSERT INTO notificacoes (usuario_id, administrador_id, tipo, mensagem, itens_ids, status, data_envio) VALUES (?, ?, 'Transferência', ?, ?, 'Pendente', NOW())";
    $stmt_notificacao = $pdo->prepare($sql_notificacao);
    // Executa a inserção da notificação
    $stmt_notificacao->execute([$novo_responsavel_id, $administrador_id, $mensagem_notificacao, $itens_ids_string]);

    // --- Finalização da Transação ---
    // Se todas as operações foram bem-sucedidas, confirma a transação
    $pdo->commit();
    // Retorna uma mensagem de sucesso
    echo json_encode(['success' => true, 'message' => 'Itens movimentados e notificação enviada com sucesso!']);

} catch (Exception $e) {
    // --- Tratamento de Erro ---
    // Se qualquer operação falhar, desfaz a transação
    $pdo->rollBack();
    // Retorna uma mensagem de erro com os detalhes da exceção
    echo json_encode(['success' => false, 'message' => 'Erro ao processar a movimentação: ' . $e->getMessage()]);
}
?>
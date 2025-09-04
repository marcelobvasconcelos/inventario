<?php
/**
 * API para atualizar o estado de um item
 * 
 * Este script permite que administradores atualizem o estado de um item
 * através de uma requisição AJAX.
 * 
 * Funcionalidades:
 * - Verifica se o usuário está logado e é administrador
 * - Valida os dados recebidos
 * - Atualiza o estado do item no banco de dados
 * - Retorna uma resposta JSON indicando o sucesso ou falha da operação
 */

session_start();

// Verificar se o usuário está logado e é administrador
// Apenas administradores têm permissão para alterar o estado dos itens
if (!isset($_SESSION['id']) || $_SESSION['permissao'] != 'Administrador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

// Incluir a conexão com o banco de dados
require_once '../config/db.php';

// Verificar se os dados foram enviados via POST
// Este endpoint só aceita requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Obter os dados do POST
// item_id: ID do item a ser atualizado
// novo_estado: Novo valor para o campo estado do item
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$novo_estado = isset($_POST['novo_estado']) ? trim($_POST['novo_estado']) : '';

// Validar os dados
// Verifica se o ID do item é válido e se o novo estado foi fornecido
if ($item_id <= 0 || empty($novo_estado)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

// Verificar se o novo estado é válido
// Lista de estados válidos para o campo 'estado' da tabela 'itens'
$estados_validos = ['Em uso', 'Ocioso', 'Recuperável', 'Inservível'];
if (!in_array($novo_estado, $estados_validos)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Estado inválido.']);
    exit;
}

// Preparar e executar a consulta para atualizar o estado do item
// Utiliza prepared statements para prevenir injeção de SQL
$sql = "UPDATE itens SET estado = ? WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    // Vincular os parâmetros à consulta preparada
    mysqli_stmt_bind_param($stmt, "si", $novo_estado, $item_id);
    
    // Executar a consulta
    if (mysqli_stmt_execute($stmt)) {
        // Verificar se alguma linha foi afetada pela atualização
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            // Sucesso: estado atualizado
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Estado atualizado com sucesso.', 
                'novo_estado' => $novo_estado
            ]);
        } else {
            // Nenhuma linha foi atualizada (item não encontrado)
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Nenhuma linha foi atualizada. Verifique se o item existe.'
            ]);
        }
    } else {
        // Erro ao executar a consulta
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao executar a consulta: ' . mysqli_error($link)
        ]);
    }
    
    // Fechar a declaração preparada
    mysqli_stmt_close($stmt);
} else {
    // Erro ao preparar a consulta
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao preparar a consulta: ' . mysqli_error($link)
    ]);
}

// Fechar a conexão com o banco de dados
mysqli_close($link);
?>
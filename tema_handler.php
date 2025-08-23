<?php
// tema_handler.php - Manipula a seleção de temas

// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/config/db.php';

// Verifica se é uma requisição POST para salvar o tema
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tema = isset($_POST['tema']) ? trim($_POST['tema']) : '';
    
    // Valida o tema selecionado
    $temas_validos = ['padrao', 'azul', 'verde', 'roxo', 'altocontraste'];
    if (!in_array($tema, $temas_validos)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tema inválido']);
        exit;
    }
    
    // Atualiza o tema preferido do usuário no banco de dados
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET tema_preferido = ? WHERE id = ?");
        $stmt->execute([$tema, $_SESSION['id']]);
        
        // Atualiza a sessão com o novo tema
        $_SESSION['tema_preferido'] = $tema;
        
        echo json_encode(['success' => true, 'message' => 'Tema atualizado com sucesso', 'tema' => $tema]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar tema']);
    }
    exit;
}

// Verifica se é uma requisição GET para obter o tema atual
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Obtém o tema preferido do usuário do banco de dados
        $stmt = $pdo->prepare("SELECT tema_preferido FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['id']]);
        $tema = $stmt->fetchColumn();
        
        // Se não houver tema definido, usa o padrão
        if (!$tema) {
            $tema = 'padrao';
        }
        
        echo json_encode(['tema' => $tema]);
    } catch (Exception $e) {
        echo json_encode(['tema' => 'padrao']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método não permitido']);
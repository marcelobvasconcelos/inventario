<?php
// test_tema.php - Página de teste para verificar o tema atual

// Inicia a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/config/db.php';

// Obtém o tema preferido do usuário
$tema_usuario = 'padrao';
if (isset($_SESSION['id'])) {
    try {
        $stmt_tema = $pdo->prepare("SELECT tema_preferido FROM usuarios WHERE id = ?");
        $stmt_tema->execute([$_SESSION['id']]);
        $tema_usuario = $stmt_tema->fetchColumn();
        
        // Se não houver tema definido, usa o padrão
        if (!$tema_usuario) {
            $tema_usuario = 'padrao';
        }
    } catch (Exception $e) {
        $tema_usuario = 'padrao';
    }
}

echo "Tema atual do usuário: " . $tema_usuario;

// Verifica se foi feita uma requisição para mudar o tema
if (isset($_GET['mudar_tema'])) {
    $novo_tema = $_GET['mudar_tema'];
    
    // Valida o tema selecionado
    $temas_validos = ['padrao', 'azul', 'verde', 'roxo'];
    if (in_array($novo_tema, $temas_validos)) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET tema_preferido = ? WHERE id = ?");
            $stmt->execute([$novo_tema, $_SESSION['id']]);
            
            // Atualiza a sessão com o novo tema
            $_SESSION['tema_preferido'] = $novo_tema;
            
            echo "<br>Tema atualizado para: " . $novo_tema;
        } catch (Exception $e) {
            echo "<br>Erro ao atualizar tema: " . $e->getMessage();
        }
    } else {
        echo "<br>Tema inválido";
    }
}

echo "<br><br>";
echo "<a href='test_tema.php?mudar_tema=padrao'>Mudar para Padrão</a> | ";
echo "<a href='test_tema.php?mudar_tema=azul'>Mudar para Azul</a> | ";
echo "<a href='test_tema.php?mudar_tema=verde'>Mudar para Verde</a> | ";
echo "<a href='test_tema.php?mudar_tema=roxo'>Mudar para Roxo</a>";
?>
<?php
// debug_tema.php - Página de debug para verificar o sistema de temas

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

echo "<h2>Debug do Sistema de Temas</h2>";

// Verifica a sessão
echo "<h3>Sessão:</h3>";
echo "ID do usuário: " . (isset($_SESSION['id']) ? $_SESSION['id'] : 'Não definido') . "<br>";
echo "Tema preferido na sessão: " . (isset($_SESSION['tema_preferido']) ? $_SESSION['tema_preferido'] : 'Não definido') . "<br>";

// Verifica o banco de dados
if (isset($_SESSION['id'])) {
    try {
        echo "<h3>Banco de Dados:</h3>";
        $stmt = $pdo->prepare("SELECT id, nome, tema_preferido FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            echo "ID: " . $usuario['id'] . "<br>";
            echo "Nome: " . $usuario['nome'] . "<br>";
            echo "Tema preferido no banco: " . ($usuario['tema_preferido'] ? $usuario['tema_preferido'] : 'Não definido') . "<br>";
        } else {
            echo "Usuário não encontrado no banco de dados.<br>";
        }
    } catch (Exception $e) {
        echo "Erro ao acessar o banco de dados: " . $e->getMessage() . "<br>";
    }
}

// Testa a atualização do tema
if (isset($_GET['teste_atualizacao'])) {
    echo "<h3>Teste de Atualização:</h3>";
    $temas = ['padrao', 'azul', 'verde', 'roxo'];
    $tema_teste = $temas[array_rand($temas)];
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET tema_preferido = ? WHERE id = ?");
        $stmt->execute([$tema_teste, $_SESSION['id']]);
        echo "Tema atualizado para: " . $tema_teste . "<br>";
        
        // Atualiza a sessão
        $_SESSION['tema_preferido'] = $tema_teste;
        echo "Sessão atualizada.<br>";
    } catch (Exception $e) {
        echo "Erro ao atualizar tema: " . $e->getMessage() . "<br>";
    }
}

// Testa a leitura do tema
if (isset($_GET['teste_leitura'])) {
    echo "<h3>Teste de Leitura:</h3>";
    try {
        // Inclui o header para testar a leitura do tema
        ob_start();
        include 'includes/header.php';
        $header_content = ob_get_clean();
        
        // Verifica se o tema correto foi carregado
        $tema_sessao = isset($_SESSION['tema_preferido']) ? $_SESSION['tema_preferido'] : 'padrao';
        if (strpos($header_content, "tema_" . $tema_sessao . ".css") !== false) {
            echo "Tema carregado corretamente: " . $tema_sessao . "<br>";
        } else {
            echo "Erro ao carregar o tema. Tema na sessão: " . $tema_sessao . "<br>";
        }
    } catch (Exception $e) {
        echo "Erro ao testar leitura do tema: " . $e->getMessage() . "<br>";
    }
}

echo "<br><a href='debug_tema.php?teste_atualizacao=1'>Testar Atualização de Tema</a><br>";
echo "<a href='debug_tema.php?teste_leitura=1'>Testar Leitura de Tema</a><br>";
echo "<a href='index.php'>Voltar para o início</a>";
?>
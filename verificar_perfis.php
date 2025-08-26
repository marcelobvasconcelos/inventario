<?php
require_once 'config/db.php';

try {
    // Verificar perfis existentes
    $stmt = $pdo->prepare("SELECT id, nome FROM perfis");
    $stmt->execute();
    $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Perfis existentes:\n";
    foreach ($perfis as $perfil) {
        echo "- ID: " . $perfil['id'] . ", Nome: " . $perfil['nome'] . "\n";
    }
    
    // Verificar se o usuário "Lixeira" já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt->execute();
    $usuario_existente = $stmt->fetchColumn();
    
    if ($usuario_existente) {
        echo "\nUsuário 'Lixeira' já existe com ID: " . $usuario_existente . "\n";
    } else {
        echo "\nUsuário 'Lixeira' não encontrado.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
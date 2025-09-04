<?php
// add_perfil_almoxarife.php - Script para adicionar o perfil de Almoxarife ao banco de dados
require_once 'config/db.php';

try {
    // Verificar se o perfil já existe
    $stmt = $pdo->prepare("SELECT id FROM perfis WHERE nome = ?");
    $stmt->execute(['Almoxarife']);
    $perfil = $stmt->fetch();
    
    if (!$perfil) {
        // Inserir o novo perfil
        $stmt = $pdo->prepare("INSERT INTO perfis (nome) VALUES (?)");
        $stmt->execute(['Almoxarife']);
        echo "Perfil 'Almoxarife' adicionado com sucesso!\n";
    } else {
        echo "Perfil 'Almoxarife' já existe no banco de dados.\n";
    }
} catch (Exception $e) {
    echo "Erro ao adicionar perfil: " . $e->getMessage() . "\n";
}
?>
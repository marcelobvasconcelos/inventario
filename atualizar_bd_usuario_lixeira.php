<?php
// Script para criar o usuário "Lixeira" no banco de dados
require_once 'config/db.php';

try {
    // Verificar se o perfil 'Visualizador' existe (perfil mais adequado para a lixeira)
    $stmt = $pdo->prepare("SELECT id FROM perfis WHERE nome = 'Visualizador'");
    $stmt->execute();
    $perfil_id = $stmt->fetchColumn();
    
    if (!$perfil_id) {
        echo "Erro: Perfil 'Visualizador' não encontrado.
";
        exit(1);
    }
    
    // Verificar se o usuário "Lixeira" já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt->execute();
    $usuario_existente = $stmt->fetchColumn();
    
    if ($usuario_existente) {
        echo "Usuário 'Lixeira' já existe.
";
        exit(0);
    }
    
    // Criar o usuário "Lixeira"
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, status, permissao_id) VALUES (?, ?, ?, ?, ?)");
    $resultado = $stmt->execute(['Lixeira', 'lixeira@inventario.local', '', 'aprovado', $perfil_id]);
    
    if ($resultado) {
        echo "Usuário 'Lixeira' criado com sucesso!
";
    } else {
        echo "Erro ao criar o usuário 'Lixeira'.
";
        exit(1);
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "
";
    exit(1);
}
?>
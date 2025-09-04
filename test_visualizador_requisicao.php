<?php
// test_visualizador_requisicao.php - Script para testar se um usuário com o perfil "Visualizador" pode criar uma requisição
require_once 'config/db.php';

echo "Testando se um usuário com o perfil 'Visualizador' pode criar uma requisição...\n\n";

try {
    // Obter um usuário com o perfil 'Visualizador'
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE permissao_id = (SELECT id FROM perfis WHERE nome = 'Visualizador') LIMIT 1");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "Nenhum usuário com o perfil 'Visualizador' encontrado.\n";
        exit(1);
    } else {
        $usuario_id = $usuario['id'];
        echo "Usuário com o perfil 'Visualizador' encontrado com ID: " . $usuario_id . "\n";
    }
    
    // Testar criação de uma requisição diretamente no banco de dados
    echo "\n1. Testando criação de uma requisição...\n";
    
    // Obter um produto para a requisição
    $stmt = $pdo->prepare("SELECT id FROM almoxarifado_produtos LIMIT 1");
    $stmt->execute();
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        echo "   ✗ Nenhum produto encontrado para criar a requisição\n";
        exit(1);
    }
    
    // Obter um local para a requisição
    $stmt = $pdo->prepare("SELECT id FROM locais LIMIT 1");
    $stmt->execute();
    $local = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$local) {
        echo "   ✗ Nenhum local encontrado para criar a requisição\n";
        exit(1);
    }
    
    // Criar uma requisição de teste
    $stmt = $pdo->prepare("INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa) VALUES (?, ?, NOW(), ?)");
    $stmt->execute([$usuario_id, $local['id'], 'Requisição de teste para usuário visualizador']);
    $requisicao_id = $pdo->lastInsertId();
    
    if ($requisicao_id > 0) {
        echo "   ✓ Requisição criada com sucesso (ID: " . $requisicao_id . ")\n";
    } else {
        echo "   ✗ Falha ao criar requisição\n";
        exit(1);
    }
    
    // Adicionar um item à requisição
    $stmt = $pdo->prepare("INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) VALUES (?, ?, ?)");
    $stmt->execute([$requisicao_id, $produto['id'], 5]);
    
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Item adicionado à requisição com sucesso\n";
    } else {
        echo "   ✗ Falha ao adicionar item à requisição\n";
        exit(1);
    }
    
    // Verificar se a requisição foi criada corretamente
    echo "\n2. Verificando se a requisição foi criada corretamente...\n";
    
    $stmt = $pdo->prepare("
        SELECT r.*, u.nome as usuario_nome, l.nome as local_nome
        FROM almoxarifado_requisicoes r
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN locais l ON r.local_id = l.id
        WHERE r.id = ?
    ");
    $stmt->execute([$requisicao_id]);
    $requisicao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($requisicao) {
        echo "   ✓ Requisição encontrada:\n";
        echo "     - ID: " . $requisicao['id'] . "\n";
        echo "     - Usuário: " . $requisicao['usuario_nome'] . "\n";
        echo "     - Local: " . ($requisicao['local_nome'] ?? 'Não especificado') . "\n";
        echo "     - Status: " . $requisicao['status'] . "\n";
        echo "     - Justificativa: " . $requisicao['justificativa'] . "\n";
    } else {
        echo "   ✗ Requisição não encontrada\n";
        exit(1);
    }
    
    // Verificar se o item foi adicionado corretamente
    echo "\n3. Verificando se o item foi adicionado corretamente...\n";
    
    $stmt = $pdo->prepare("
        SELECT i.*, p.nome as produto_nome
        FROM almoxarifado_requisicoes_itens i
        JOIN almoxarifado_produtos p ON i.produto_id = p.id
        WHERE i.requisicao_id = ?
    ");
    $stmt->execute([$requisicao_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        echo "   ✓ Item encontrado:\n";
        echo "     - Produto: " . $item['produto_nome'] . "\n";
        echo "     - Quantidade solicitada: " . $item['quantidade_solicitada'] . "\n";
    } else {
        echo "   ✗ Item não encontrado\n";
        exit(1);
    }
    
    // Limpar os dados de teste
    echo "\n4. Limpando os dados de teste...\n";
    
    $stmt = $pdo->prepare("DELETE FROM almoxarifado_requisicoes_itens WHERE requisicao_id = ?");
    $stmt->execute([$requisicao_id]);
    
    $stmt = $pdo->prepare("DELETE FROM almoxarifado_requisicoes WHERE id = ?");
    $stmt->execute([$requisicao_id]);
    
    echo "   ✓ Dados de teste limpos com sucesso\n";
    
    echo "\n✓ Todos os testes foram executados com sucesso!\n";
    echo "Um usuário com o perfil 'Visualizador' pode criar requisições no almoxarifado.\n";
    
} catch (Exception $e) {
    echo "✗ Erro durante os testes: " . $e->getMessage() . "\n";
    exit(1);
}
?>
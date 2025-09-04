<?php
// test_almoxarifado.php - Script para testar a funcionalidade do módulo de almoxarifado
require_once 'config/db.php';

echo "Testando o módulo de almoxarifado...\n\n";

try {
    // Testar conexão com as tabelas do almoxarifado
    echo "1. Verificando tabelas do almoxarifado...\n";
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'almoxarifado_produtos'");
    $stmt->execute();
    $tabela_produtos = $stmt->fetch();
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'almoxarifado_requisicoes'");
    $stmt->execute();
    $tabela_requisicoes = $stmt->fetch();
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'almoxarifado_requisicoes_itens'");
    $stmt->execute();
    $tabela_itens = $stmt->fetch();
    
    if ($tabela_produtos && $tabela_requisicoes && $tabela_itens) {
        echo "   ✓ Tabelas do almoxarifado encontradas\n";
    } else {
        echo "   ✗ Tabelas do almoxarifado não encontradas\n";
        exit(1);
    }
    
    // Testar inserção de um produto
    echo "\n2. Testando inserção de produto...\n";
    
    $stmt = $pdo->prepare("INSERT INTO almoxarifado_produtos (nome, descricao, unidade_medida, estoque_atual, estoque_minimo) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Teste de Produto', 'Produto para testes', 'unidades', 100, 10]);
    $produto_id = $pdo->lastInsertId();
    
    if ($produto_id > 0) {
        echo "   ✓ Produto inserido com sucesso (ID: $produto_id)\n";
    } else {
        echo "   ✗ Falha ao inserir produto\n";
        exit(1);
    }
    
    // Testar consulta de produtos
    echo "\n3. Testando consulta de produtos...\n";
    
    $stmt = $pdo->prepare("SELECT * FROM almoxarifado_produtos WHERE id = ?");
    $stmt->execute([$produto_id]);
    $produto = $stmt->fetch();
    
    if ($produto) {
        echo "   ✓ Produto encontrado: " . $produto['nome'] . "\n";
    } else {
        echo "   ✗ Produto não encontrado\n";
        exit(1);
    }
    
    // Testar atualização de produto
    echo "\n4. Testando atualização de produto...\n";
    
    $stmt = $pdo->prepare("UPDATE almoxarifado_produtos SET estoque_atual = ? WHERE id = ?");
    $stmt->execute([90, $produto_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Produto atualizado com sucesso\n";
    } else {
        echo "   ✗ Falha ao atualizar produto\n";
        exit(1);
    }
    
    // Testar inserção de requisição
    echo "\n5. Testando inserção de requisição...\n";
    
    // Obter um usuário válido
    $stmt = $pdo->prepare("SELECT id FROM usuarios LIMIT 1");
    $stmt->execute();
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo "   ✗ Nenhum usuário encontrado para testar requisição\n";
        exit(1);
    }
    
    $usuario_id = $usuario['id'];
    
    $stmt = $pdo->prepare("INSERT INTO almoxarifado_requisicoes (usuario_id, data_requisicao, justificativa) VALUES (?, NOW(), ?)");
    $stmt->execute([$usuario_id, 'Requisição de teste']);
    $requisicao_id = $pdo->lastInsertId();
    
    if ($requisicao_id > 0) {
        echo "   ✓ Requisição inserida com sucesso (ID: $requisicao_id)\n";
    } else {
        echo "   ✗ Falha ao inserir requisição\n";
        exit(1);
    }
    
    // Testar inserção de item de requisição
    echo "\n6. Testando inserção de item de requisição...\n";
    
    $stmt = $pdo->prepare("INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) VALUES (?, ?, ?)");
    $stmt->execute([$requisicao_id, $produto_id, 5]);
    
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Item de requisição inserido com sucesso\n";
    } else {
        echo "   ✗ Falha ao inserir item de requisição\n";
        exit(1);
    }
    
    // Testar consulta de requisições
    echo "\n7. Testando consulta de requisições...\n";
    
    $stmt = $pdo->prepare("
        SELECT r.*, u.nome as usuario_nome
        FROM almoxarifado_requisicoes r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$requisicao_id]);
    $requisicao = $stmt->fetch();
    
    if ($requisicao) {
        echo "   ✓ Requisição encontrada: " . $requisicao['justificativa'] . " (Usuário: " . $requisicao['usuario_nome'] . ")\n";
    } else {
        echo "   ✗ Requisição não encontrada\n";
        exit(1);
    }
    
    // Testar consulta de itens de requisição
    echo "\n8. Testando consulta de itens de requisição...\n";
    
    $stmt = $pdo->prepare("
        SELECT i.*, p.nome as produto_nome
        FROM almoxarifado_requisicoes_itens i
        JOIN almoxarifado_produtos p ON i.produto_id = p.id
        WHERE i.requisicao_id = ?
    ");
    $stmt->execute([$requisicao_id]);
    $itens = $stmt->fetchAll();
    
    if (count($itens) > 0) {
        echo "   ✓ Itens de requisição encontrados: " . count($itens) . " itens\n";
    } else {
        echo "   ✗ Itens de requisição não encontrados\n";
        exit(1);
    }
    
    // Testar atualização de status da requisição
    echo "\n9. Testando atualização de status da requisição...\n";
    
    $stmt = $pdo->prepare("UPDATE almoxarifado_requisicoes SET status = 'aprovada' WHERE id = ?");
    $stmt->execute([$requisicao_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Status da requisição atualizado com sucesso\n";
    } else {
        echo "   ✗ Falha ao atualizar status da requisição\n";
        exit(1);
    }
    
    // Testar exclusão de registros de teste
    echo "\n10. Testando exclusão de registros de teste...\n";
    
    // Excluir itens de requisição
    $stmt = $pdo->prepare("DELETE FROM almoxarifado_requisicoes_itens WHERE requisicao_id = ?");
    $stmt->execute([$requisicao_id]);
    
    // Excluir requisição
    $stmt = $pdo->prepare("DELETE FROM almoxarifado_requisicoes WHERE id = ?");
    $stmt->execute([$requisicao_id]);
    
    // Excluir produto
    $stmt = $pdo->prepare("DELETE FROM almoxarifado_produtos WHERE id = ?");
    $stmt->execute([$produto_id]);
    
    echo "   ✓ Registros de teste excluídos com sucesso\n";
    
    echo "\n✓ Todos os testes do módulo de almoxarifado foram executados com sucesso!\n";
    
} catch (Exception $e) {
    echo "✗ Erro durante os testes: " . $e->getMessage() . "\n";
    exit(1);
}
?>
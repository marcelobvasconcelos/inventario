<?php
// testar_requisicao.php - Script para testar a funcionalidade de requisição
require_once 'config/db.php';

echo "Testando a funcionalidade de requisição...\n";

// Verificar se existem produtos no almoxarifado
$sql_check = "SELECT id, nome, estoque_atual FROM almoxarifado_produtos WHERE estoque_atual > 0 LIMIT 3";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute();
$produtos = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

if (count($produtos) == 0) {
    echo "Não há produtos disponíveis no almoxarifado para testar.\n";
    exit;
}

// Verificar se existem locais
$sql_locais = "SELECT id, nome FROM locais LIMIT 1";
$stmt_locais = $pdo->prepare($sql_locais);
$stmt_locais->execute();
$local = $stmt_locais->fetch(PDO::FETCH_ASSOC);

if (!$local) {
    echo "Não há locais cadastrados para testar.\n";
    exit;
}

// Verificar se existem usuários com permissão de Visualizador
$sql_usuarios = "SELECT u.id, u.nome FROM usuarios u JOIN perfis p ON u.permissao_id = p.id WHERE p.nome = 'Visualizador' AND u.status = 'aprovado' LIMIT 1";
$stmt_usuarios = $pdo->prepare($sql_usuarios);
$stmt_usuarios->execute();
$usuario = $stmt_usuarios->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "Não há usuários com perfil de Visualizador para testar.\n";
    exit;
}

echo "Iniciando teste de requisição...\n";

// Iniciar transação
$pdo->beginTransaction();

try {
    // Inserir a requisição
    $sql_requisicao = "INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa) VALUES (?, ?, NOW(), ?)";
    $stmt_requisicao = $pdo->prepare($sql_requisicao);
    $stmt_requisicao->execute([$usuario['id'], $local['id'], 'Teste de requisição de produtos']);
    $requisicao_id = $pdo->lastInsertId();
    
    echo "Requisição criada com ID: $requisicao_id\n";
    
    // Inserir itens da requisição
    foreach ($produtos as $produto) {
        $quantidade = min(2, $produto['estoque_atual']); // Solicitar no máximo 2 unidades de cada produto
        
        $sql_item = "INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) VALUES (?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        $stmt_item->execute([$requisicao_id, $produto['id'], $quantidade]);
        
        echo "Item adicionado: {$produto['nome']} (ID: {$produto['id']}) - Quantidade: $quantidade\n";
    }
    
    // Commit da transação
    $pdo->commit();
    
    echo "Requisição de teste criada com sucesso!\n";
    echo "Código da requisição: REQ-" . str_pad($requisicao_id, 6, '0', STR_PAD_LEFT) . "\n";
    
} catch (Exception $e) {
    // Rollback da transação em caso de erro
    $pdo->rollback();
    
    echo "Erro ao criar requisição de teste: " . $e->getMessage() . "\n";
}
?>
<?php
// criar_requisicoes_exemplo.php - Script para criar requisições de exemplo no almoxarifado
require_once 'config/db.php';

echo "Criando requisições de exemplo no almoxarifado...\n\n";

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Obter alguns usuários para fazer as requisições
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE status = 'aprovado' LIMIT 3");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($usuarios) < 3) {
        echo "Não há usuários suficientes aprovados para criar requisições de exemplo.\n";
        exit(1);
    }
    
    // Obter alguns produtos para as requisições
    $stmt = $pdo->prepare("SELECT id FROM almoxarifado_produtos LIMIT 5");
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($produtos) < 5) {
        echo "Não há produtos suficientes para criar requisições de exemplo.\n";
        exit(1);
    }
    
    // Criar requisições de exemplo
    echo "1. Criando requisições de exemplo...\n";
    
    $requisicoes = [
        [
            'usuario_id' => $usuarios[0]['id'],
            'justificativa' => 'Necessidade de materiais para nova turma',
            'itens' => [
                ['produto_id' => $produtos[0]['id'], 'quantidade' => 50],
                ['produto_id' => $produtos[1]['id'], 'quantidade' => 50],
                ['produto_id' => $produtos[2]['id'], 'quantidade' => 30]
            ]
        ],
        [
            'usuario_id' => $usuarios[1]['id'],
            'justificativa' => 'Reposição de estoque da secretaria',
            'itens' => [
                ['produto_id' => $produtos[3]['id'], 'quantidade' => 20],
                ['produto_id' => $produtos[4]['id'], 'quantidade' => 10]
            ]
        ],
        [
            'usuario_id' => $usuarios[2]['id'],
            'justificativa' => 'Preparação para evento escolar',
            'itens' => [
                ['produto_id' => $produtos[0]['id'], 'quantidade' => 100],
                ['produto_id' => $produtos[3]['id'], 'quantidade' => 50],
                ['produto_id' => $produtos[4]['id'], 'quantidade' => 20]
            ]
        ]
    ];
    
    $stmt_requisicao = $pdo->prepare("INSERT INTO almoxarifado_requisicoes (usuario_id, data_requisicao, justificativa) VALUES (?, NOW(), ?)");
    $stmt_item = $pdo->prepare("INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) VALUES (?, ?, ?)");
    
    foreach ($requisicoes as $index => $requisicao) {
        // Inserir a requisição
        $stmt_requisicao->execute([$requisicao['usuario_id'], $requisicao['justificativa']]);
        $requisicao_id = $pdo->lastInsertId();
        
        echo "   ✓ Requisição #" . $requisicao_id . " criada para usuário ID " . $requisicao['usuario_id'] . "\n";
        
        // Inserir os itens da requisição
        foreach ($requisicao['itens'] as $item) {
            $stmt_item->execute([$requisicao_id, $item['produto_id'], $item['quantidade']]);
            echo "     - Item produto ID " . $item['produto_id'] . " (" . $item['quantidade'] . " unidades)\n";
        }
    }
    
    // Confirmar transação
    $pdo->commit();
    
    echo "\n✓ " . count($requisicoes) . " requisições de exemplo criadas com sucesso!\n";
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $pdo->rollback();
    
    echo "✗ Erro ao criar requisições de exemplo: " . $e->getMessage() . "\n";
    exit(1);
}
?>
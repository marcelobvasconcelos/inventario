<?php
// Script para testar a funcionalidade de restauração de itens em massa da lixeira
require_once 'config/db.php';

try {
    // Obter o ID do usuário "Lixeira"
    $stmt_lixeira = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt_lixeira->execute();
    $lixeira = $stmt_lixeira->fetch(PDO::FETCH_ASSOC);
    
    if (!$lixeira) {
        echo "Erro: Usuário 'Lixeira' não encontrado.
";
        exit(1);
    }
    
    $lixeira_id = $lixeira['id'];
    echo "ID do usuário 'Lixeira': " . $lixeira_id . "
";
    
    // Encontrar itens na lixeira para restaurar
    $stmt_itens = $pdo->prepare("SELECT id, nome, patrimonio_novo FROM itens WHERE estado = 'Excluido' AND responsavel_id = ? LIMIT 3");
    $stmt_itens->execute([$lixeira_id]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($itens) == 0) {
        echo "Nenhum item na lixeira disponível para teste.
";
        exit(0);
    }
    
    echo "Itens encontrados na lixeira:
";
    $item_ids = [];
    foreach ($itens as $item) {
        echo "- ID: " . $item['id'] . ", Nome: " . $item['nome'] . ", Patrimônio: " . $item['patrimonio_novo'] . "
";
        $item_ids[] = $item['id'];
    }
    
    // Obter um usuário e local válidos para restaurar os itens
    $stmt_usuario = $pdo->prepare("SELECT id FROM usuarios WHERE id != ? AND nome != 'Lixeira' LIMIT 1");
    $stmt_usuario->execute([$lixeira_id]);
    $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "Nenhum usuário disponível para teste.
";
        exit(0);
    }
    
    $novo_responsavel_id = $usuario['id'];
    echo "Novo responsável: " . $novo_responsavel_id . "
";
    
    // Obter um local válido
    $stmt_local = $pdo->prepare("SELECT id FROM locais LIMIT 1");
    $stmt_local->execute();
    $local = $stmt_local->fetch(PDO::FETCH_ASSOC);
    
    if (!$local) {
        echo "Nenhum local disponível para teste.
";
        exit(0);
    }
    
    $novo_local_id = $local['id'];
    echo "Novo local: " . $novo_local_id . "
";
    
    // Preparar os dados para a requisição
    $data = [
        'item_ids' => $item_ids,
        'novo_local_id' => $novo_local_id,
        'novo_responsavel_id' => $novo_responsavel_id
    ];
    
    // Converter os dados para JSON
    $json_data = json_encode($data);
    
    // Simular a requisição POST para a API
    echo "
Simulando requisição POST para api/restaurar_itens_em_massa.php...
";
    
    // Criar um contexto para a requisição
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_data)
            ],
            'content' => $json_data
        ]
    ]);
    
    // Fazer a requisição (isso é apenas uma simulação, não vai funcionar neste contexto)
    echo "Dados enviados:
";
    echo $json_data . "
";
    
    echo "
Teste concluído. Para testar realmente, execute a API com os dados acima.
";
    
} catch (Exception $e) {
    echo "Erro no teste: " . $e->getMessage() . "
";
    exit(1);
}
?>
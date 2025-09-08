<?php
// Script para popular a tabela almoxarifado_materiais com dados de exemplo
require_once dirname(__DIR__) . '/config/db.php';

echo "Iniciando script para popular almoxarifado...\n";

// Array de materiais de exemplo para a tabela correta
$materiais = [
    ['codigo' => 'PAP001', 'nome' => 'Resma de Papel A4', 'descricao' => 'Pacote com 500 folhas, 75g/m²', 'unidade_medida' => 'un', 'estoque_atual' => 100, 'valor_unitario' => 25.50, 'categoria' => 'Papelaria'],
    ['codigo' => 'CAN002', 'nome' => 'Caneta Esferográfica Azul', 'descricao' => 'Caixa com 50 unidades', 'unidade_medida' => 'cx', 'estoque_atual' => 20, 'valor_unitario' => 30.00, 'categoria' => 'Papelaria'],
    ['codigo' => 'TON003', 'nome' => 'Toner para Impressora HL-1212W', 'descricao' => 'Toner TN-1060, preto', 'unidade_medida' => 'un', 'estoque_atual' => 15, 'valor_unitario' => 65.00, 'categoria' => 'Suprimentos de TI'],
    ['codigo' => 'MOU004', 'nome' => 'Mouse Óptico USB', 'descricao' => 'Mouse com fio, 3 botões', 'unidade_medida' => 'un', 'estoque_atual' => 50, 'valor_unitario' => 22.90, 'categoria' => 'Periféricos'],
    ['codigo' => 'TEC005', 'nome' => 'Teclado USB ABNT2', 'descricao' => 'Teclado com fio, padrão brasileiro', 'unidade_medida' => 'un', 'estoque_atual' => 45, 'valor_unitario' => 45.00, 'categoria' => 'Periféricos'],
    ['codigo' => 'AGU006', 'nome' => 'Água Mineral 500ml', 'descricao' => 'Garrafa sem gás', 'unidade_medida' => 'un', 'estoque_atual' => 200, 'valor_unitario' => 1.50, 'categoria' => 'Copa e Cozinha'],
    ['codigo' => 'CAF007', 'nome' => 'Café em Pó 500g', 'descricao' => 'Café torrado e moído', 'unidade_medida' => 'pct', 'estoque_atual' => 30, 'valor_unitario' => 15.00, 'categoria' => 'Copa e Cozinha'],
];

// Inserir materiais
echo "Inserindo materiais na tabela almoxarifado_materiais...\n";

$sql = "INSERT INTO almoxarifado_materiais (codigo, nome, descricao, unidade_medida, estoque_atual, valor_unitario, categoria, status) VALUES (:codigo, :nome, :descricao, :unidade_medida, :estoque_atual, :valor_unitario, :categoria, 'ativo')";

foreach ($materiais as $material) {
    // Verificar se o material (pelo código) já existe
    $stmt_check = $pdo->prepare("SELECT id FROM almoxarifado_materiais WHERE codigo = ?");
    $stmt_check->execute([$material['codigo']]);
    
    if ($stmt_check->rowCount() == 0) {
        $stmt_insert = $pdo->prepare($sql);
        $stmt_insert->execute([
            ':codigo' => $material['codigo'],
            ':nome' => $material['nome'],
            ':descricao' => $material['descricao'],
            ':unidade_medida' => $material['unidade_medida'],
            ':estoque_atual' => $material['estoque_atual'],
            ':valor_unitario' => $material['valor_unitario'],
            ':categoria' => $material['categoria']
        ]);
        echo "- Material '{$material['nome']}' inserido.\n";
    } else {
        echo "- Material com código '{$material['codigo']}' já existe, pulando.\n";
    }
}

echo "População do banco de dados do almoxarifado concluída!\n";
?>

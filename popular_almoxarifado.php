<?php
// popular_almoxarifado.php - Script para popular o almoxarifado com produtos de exemplo
require_once 'config/db.php';

echo "Verificando se o almoxarifado está vazio...\n";

// Verificar se já existem produtos
$sql_check = "SELECT COUNT(*) as count FROM almoxarifado_produtos";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute();
$result = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    echo "Almoxarifado vazio. Inserindo produtos de exemplo...\n";
    
    $produtos = [
        ['Caneta esferográfica', 'Caneta esferográfica azul, corpo plástico', 'Unidade', 100, 20],
        ['Lápis preto', 'Lápis preto nº 2', 'Unidade', 50, 10],
        ['Borracha', 'Borracha branca para lápis', 'Unidade', 30, 5],
        ['Caderno universitário', 'Caderno universitário 1 matéria, 200 folhas', 'Unidade', 20, 5],
        ['Clips', 'Clips tamanho médio, pacote com 100 unidades', 'Pacote', 10, 2],
        ['Grampos', 'Grampos para grampeador, caixa com 1000 unidades', 'Caixa', 5, 1],
        ['Papel A4', 'Papel A4 branco, pacote com 500 folhas', 'Pacote', 15, 3],
        ['Envelope', 'Envelope branco tamanho comercial (105x230mm)', 'Unidade', 100, 20],
        ['Fita adesiva', 'Fita adesiva transparente 12mm x 50m', 'Unidade', 25, 5],
        ['Marcador de texto', 'Marcador de texto amarelo, ponta chanfrada', 'Unidade', 15, 3]
    ];
    
    $sql_insert = "INSERT INTO almoxarifado_produtos (nome, descricao, unidade_medida, estoque_atual, estoque_minimo) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql_insert);
    
    foreach ($produtos as $produto) {
        try {
            $stmt_insert->execute($produto);
            echo "Produto '{$produto[0]}' inserido com sucesso.\n";
        } catch (Exception $e) {
            echo "Erro ao inserir produto '{$produto[0]}': " . $e->getMessage() . "\n";
        }
    }
    
    echo "População do almoxarifado concluída.\n";
} else {
    echo "Almoxarifado já possui produtos. Nenhuma ação necessária.\n";
}
?>
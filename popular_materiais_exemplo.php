<?php
// Script para popular o banco de dados com exemplos de materiais

require_once 'config/db.php';

// Verificar se já existem muitos materiais cadastrados
$sql_check = "SELECT COUNT(*) as count FROM materiais";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute();
$count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];

if ($count > 5) {
    echo "Já existem materiais suficientes cadastrados no banco de dados.
";
    exit;
}

// Array de materiais de exemplo, usando os IDs de categoria existentes
$materiais = [
    ['nome' => 'Caneta Esferográfica Azul', 'qtd' => 100, 'categoria_id' => 1, 'valor_unit' => 1.50, 'nota_no' => 'NF2023001'],
    ['nome' => 'Caderno Universitário 200 folhas', 'qtd' => 50, 'categoria_id' => 1, 'valor_unit' => 12.90, 'nota_no' => 'NF2023001'],
    ['nome' => 'Clips Nº 0 Metálico', 'qtd' => 200, 'categoria_id' => 1, 'valor_unit' => 3.50, 'nota_no' => 'NF2023001'],
    ['nome' => 'Mouse Óptico USB', 'qtd' => 25, 'categoria_id' => 5, 'valor_unit' => 45.90, 'nota_no' => 'NF2023001'],
    ['nome' => 'Teclado ABNT2', 'qtd' => 15, 'categoria_id' => 5, 'valor_unit' => 89.90, 'nota_no' => 'NF2023001'],
    ['nome' => 'Monitor LED 21,5"', 'qtd' => 8, 'categoria_id' => 5, 'valor_unit' => 799.00, 'nota_no' => 'NF2023001'],
    ['nome' => 'Álcool 70% 1L', 'qtd' => 30, 'categoria_id' => 2, 'valor_unit' => 8.90, 'nota_no' => 'NF2023001'],
    ['nome' => 'Desinfetante Multiuso', 'qtd' => 20, 'categoria_id' => 2, 'valor_unit' => 12.50, 'nota_no' => 'NF2023001'],
    ['nome' => 'Vassoura de Nylon', 'qtd' => 10, 'categoria_id' => 2, 'valor_unit' => 25.00, 'nota_no' => 'NF2023001'],
    ['nome' => 'Cimento Comum 50kg', 'qtd' => 100, 'categoria_id' => 4, 'valor_unit' => 28.90, 'nota_no' => 'NF2023001'],
    ['nome' => 'Tijolo Cerâmico 6 furos', 'qtd' => 1000, 'categoria_id' => 4, 'valor_unit' => 0.85, 'nota_no' => 'NF2023001'],
    ['nome' => 'Areia Grossa', 'qtd' => 50, 'categoria_id' => 4, 'valor_unit' => 120.00, 'nota_no' => 'NF2023001'],
    ['nome' => 'Martelo com Cabo', 'qtd' => 12, 'categoria_id' => 4, 'valor_unit' => 35.00, 'nota_no' => 'NF2023001'],
    ['nome' => 'Chave de Fenda Philips', 'qtd' => 20, 'categoria_id' => 4, 'valor_unit' => 18.50, 'nota_no' => 'NF2023001'],
    ['nome' => 'Alicate Universal', 'qtd' => 15, 'categoria_id' => 4, 'valor_unit' => 29.90, 'nota_no' => 'NF2023001']
];

// Inserir materiais
echo "Inserindo materiais...
";
foreach ($materiais as $material) {
    // Verificar se o material já existe
    $sql_check_material = "SELECT id FROM materiais WHERE nome = ? AND nota_no = ?";
    $stmt_check_material = $pdo->prepare($sql_check_material);
    $stmt_check_material->execute([$material['nome'], $material['nota_no']]);
    
    if ($stmt_check_material->rowCount() == 0) {
        $sql_insert = "INSERT INTO materiais (nome, qtd, categoria_id, valor_unit, nota_no) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            $material['nome'],
            $material['qtd'],
            $material['categoria_id'],
            $material['valor_unit'],
            $material['nota_no']
        ]);
        echo "Material '{$material['nome']}' inserido com sucesso!
";
    } else {
        echo "Material '{$material['nome']}' já existe, pulando...
";
    }
}
echo "Materiais inseridos com sucesso!
";

echo "População do banco de dados concluída!
";
?>
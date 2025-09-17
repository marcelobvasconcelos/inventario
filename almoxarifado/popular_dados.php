<?php
require_once '../config/db.php';

echo "<pre>"; // Para formatar a saída

// --- DADOS DE EXEMPLO ---

// Categorias
$categorias = [
    ['numero' => 'ESC001', 'descricao' => 'Material de Escritório'],
    ['numero' => 'ELE001', 'descricao' => 'Componentes Eletrônicos'],
    ['numero' => 'LMP001', 'descricao' => 'Material de Limpeza'],
    ['numero' => 'FER001', 'descricao' => 'Ferramentas']
];

// Empenhos (com fornecedores embutidos)
$empenhos = [
    [
        'numero' => '2025NE001',
        'data_emissao' => '2025-09-01',
        'fornecedor' => 'Papelaria Central Ltda',
        'cnpj' => '11.111.111/0001-11',
        'status' => 'Aberto'
    ],
    [
        'numero' => '2025NE002',
        'data_emissao' => '2025-09-03',
        'fornecedor' => 'Componentes & Cia',
        'cnpj' => '22.222.222/0001-22',
        'status' => 'Aberto'
    ],
    [
        'numero' => '2025NE003',
        'data_emissao' => '2025-09-05',
        'fornecedor' => 'Limpa Tudo S/A',
        'cnpj' => '33.333.333/0001-33',
        'status' => 'Fechado'
    ]
];

// Notas Fiscais
$notas_fiscais = [
    ['nota_numero' => 'NF-00101', 'nota_valor' => 1500.75, 'empenho_numero' => '2025NE001'],
    ['nota_numero' => 'NF-00102', 'nota_valor' => 850.00, 'empenho_numero' => '2025NE001'],
    ['nota_numero' => 'NF-7754', 'nota_valor' => 12300.50, 'empenho_numero' => '2025NE002'],
    ['nota_numero' => 'NF-9889', 'nota_valor' => 430.00, 'empenho_numero' => '2025NE003']
];

// Materiais e suas entradas
$materiais_e_entradas = [
    [
        'material' => ['codigo' => 'MAT-001', 'nome' => 'Caneta Esferográfica Azul', 'descricao' => 'Caixa com 50 unidades', 'unidade_medida' => 'Caixa', 'categoria' => 'Material de Escritório', 'valor_unitario' => 25.50, 'quantidade_maxima_requisicao' => 10],
        'entrada' => ['quantidade' => 20, 'nota_fiscal' => 'NF-00101']
    ],
    [
        'material' => ['codigo' => 'MAT-002', 'nome' => 'Resma de Papel A4', 'descricao' => 'Pacote com 500 folhas, 75g/m²', 'unidade_medida' => 'Resma', 'categoria' => 'Material de Escritório', 'valor_unitario' => 22.00, 'quantidade_maxima_requisicao' => 5],
        'entrada' => ['quantidade' => 50, 'nota_fiscal' => 'NF-00101']
    ],
    [
        'material' => ['codigo' => 'ELE-101', 'nome' => 'Resistor 10k Ohm', 'descricao' => 'Pacote com 100 unidades', 'unidade_medida' => 'Pacote', 'categoria' => 'Componentes Eletrônicos', 'valor_unitario' => 15.00, 'quantidade_maxima_requisicao' => 50],
        'entrada' => ['quantidade' => 100, 'nota_fiscal' => 'NF-7754']
    ],
    [
        'material' => ['codigo' => 'ELE-205', 'nome' => 'Arduino Uno R3', 'descricao' => 'Placa de desenvolvimento', 'unidade_medida' => 'Unidade', 'categoria' => 'Componentes Eletrônicos', 'valor_unitario' => 85.75, 'quantidade_maxima_requisicao' => 2],
        'entrada' => ['quantidade' => 30, 'nota_fiscal' => 'NF-7754']
    ],
    [
        'material' => ['codigo' => 'LMP-01', 'nome' => 'Água Sanitária 5L', 'descricao' => 'Galao de 5 litros', 'unidade_medida' => 'Galão', 'categoria' => 'Material de Limpeza', 'valor_unitario' => 12.50, 'quantidade_maxima_requisicao' => 5],
        'entrada' => ['quantidade' => 30, 'nota_fiscal' => 'NF-9889']
    ]
];

try {
    $pdo->beginTransaction();

    echo "Iniciando povoamento do banco de dados...\n\n";

    // 1. Inserir Categorias
    echo "--- Inserindo Categorias ---\n";
    $sql_cat = "INSERT INTO almoxarifado_categorias (numero, descricao) VALUES (:numero, :descricao)";
    $stmt_cat = $pdo->prepare($sql_cat);
    foreach ($categorias as $cat) {
        $stmt_cat->execute($cat);
        echo "Categoria '{$cat['descricao']}' (Numero: {$cat['numero']}) inserida.\n";
    }
    echo "Categorias inseridas com sucesso!\n\n";

    // 2. Inserir Empenhos
    echo "--- Inserindo Empenhos ---\n";
    $sql_emp = "INSERT INTO empenhos_insumos (numero, data_emissao, fornecedor, cnpj, status) VALUES (:numero, :data_emissao, :fornecedor, :cnpj, :status)";
    $stmt_emp = $pdo->prepare($sql_emp);
    foreach ($empenhos as $emp) {
        $stmt_emp->execute($emp);
        echo "Empenho '{$emp['numero']}' inserido.\n";
    }
    echo "Empenhos inseridos com sucesso!\n\n";

    // 3. Inserir Notas Fiscais
    echo "--- Inserindo Notas Fiscais ---\n";
    $sql_nf = "INSERT INTO notas_fiscais (nota_numero, nota_valor, empenho_numero) VALUES (:nota_numero, :nota_valor, :empenho_numero)";
    $stmt_nf = $pdo->prepare($sql_nf);
    foreach ($notas_fiscais as $nf) {
        $stmt_nf->execute($nf);
        echo "Nota Fiscal '{$nf['nota_numero']}' inserida para o empenho '{$nf['empenho_numero']}'.\n";
    }
    echo "Notas Fiscais inseridas com sucesso!\n\n";

    // 4. Inserir Materiais em almoxarifado_materiais e registrar Entradas
    echo "--- Inserindo Materiais e Entradas ---\n";
    $sql_mat = "INSERT INTO almoxarifado_materiais (codigo, nome, descricao, unidade_medida, categoria, valor_unitario, estoque_atual, quantidade_maxima_requisicao, nota_fiscal, data_criacao, usuario_criacao) VALUES (:codigo, :nome, :descricao, :unidade_medida, :categoria, :valor_unitario, :estoque_atual, :quantidade_maxima_requisicao, NULL, NOW(), 2)";
    $stmt_mat = $pdo->prepare($sql_mat);

    $sql_ent = "INSERT INTO almoxarifado_entradas (material_id, quantidade, valor_unitario, fornecedor, nota_fiscal, data_entrada, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_ent = $pdo->prepare($sql_ent);

    foreach ($materiais_e_entradas as $item) {
        $material_data = $item['material'];
        $entrada_data = $item['entrada'];

        // Adiciona a quantidade inicial ao estoque
        $material_data['estoque_atual'] = $entrada_data['quantidade'];

        // Inserir em almoxarifado_materiais
        $stmt_mat->execute($material_data);
        $material_id = $pdo->lastInsertId();
        echo "Material '{$material_data['nome']}' inserido em almoxarifado_materiais com ID {$material_id}.\n";

        // Encontrar o fornecedor a partir da nota fiscal -> empenho
        $nota_fiscal_numero = $entrada_data['nota_fiscal'];
        $fornecedor = 'Fornecedor não encontrado';
        foreach($notas_fiscais as $nf){
            if($nf['nota_numero'] == $nota_fiscal_numero){
                foreach($empenhos as $emp){
                    if($emp['numero'] == $nf['empenho_numero']){
                        $fornecedor = $emp['fornecedor'];
                        break;
                    }
                }
                break;
            }
        }

        // Insere a entrada (referenciando material_id de almoxarifado_materiais)
        $stmt_ent->execute([
            $material_id,
            $entrada_data['quantidade'],
            $material_data['valor_unitario'],
            $fornecedor,
            $nota_fiscal_numero,
            date('Y-m-d'), // Data atual como data de entrada
            $_SESSION['id'] ?? 1 // ID do usuário logado ou 1 como padrão
        ]);
        echo "Entrada de {$entrada_data['quantidade']} unidades para o material ID {$material_id} registrada.\n";
    }
    echo "Materiais e Entradas inseridos com sucesso!\n\n";


    $pdo->commit();

    echo "===============================================\n";
    echo "BANCO DE DADOS POPULADO COM SUCESSO!\n";
    echo "===============================================\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro ao popular o banco de dados: " . $e->getMessage());
}

echo "</pre>";
?>

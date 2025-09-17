<?php
require_once '../config/db.php';

echo "<h2>Verificação da Estrutura do Banco de Dados - Almoxarifado</h2>";

$tabelas_necessarias = [
    'almoxarifado_categorias' => ['id', 'numero', 'descricao', 'data_criacao'],
    'empenhos_insumos' => ['id', 'numero', 'data_emissao', 'valor', 'saldo', 'status', 'data_criacao'],
    'notas_fiscais' => ['id', 'nota_numero', 'nota_valor', 'saldo', 'empenho_numero', 'fornecedor', 'cnpj', 'data_criacao'],
    'almoxarifado_materiais' => ['id', 'codigo', 'nome', 'descricao', 'categoria', 'unidade_medida', 'estoque_atual', 'estoque_minimo', 'valor_unitario', 'quantidade_maxima_requisicao', 'nota_fiscal', 'data_criacao', 'usuario_criacao'],
    'almoxarifado_entradas' => ['id', 'material_id', 'quantidade', 'valor_unitario', 'nota_fiscal', 'data_entrada', 'data_cadastro', 'usuario_id'],
    'almoxarifado_saidas' => ['id', 'material_id', 'quantidade', 'setor_destino', 'responsavel_saida', 'data_saida', 'observacao', 'usuario_id', 'data_cadastro'],
    'almoxarifado_movimentacoes' => ['id', 'material_id', 'tipo', 'quantidade', 'saldo_anterior', 'saldo_atual', 'data_movimentacao', 'usuario_id', 'referencia_id'],
    'almoxarifado_requisicoes' => ['id', 'usuario_id', 'data_requisicao', 'status', 'justificativa', 'observacoes_admin'],
    'almoxarifado_requisicoes_itens' => ['id', 'requisicao_id', 'produto_id', 'quantidade_solicitada', 'quantidade_entregue'],
    'empenhos_saldo_historico' => ['id', 'empenho_numero', 'saldo_anterior', 'saldo_novo', 'valor_alteracao', 'tipo_alteracao', 'referencia_id', 'referencia_tipo', 'usuario_id', 'descricao', 'data_alteracao']
];

$problemas = [];

foreach($tabelas_necessarias as $tabela => $colunas) {
    echo "<h3>Verificando tabela: $tabela</h3>";
    
    // Verificar se a tabela existe
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        if($stmt->rowCount() == 0) {
            $problemas[] = "❌ Tabela '$tabela' não existe";
            echo "<p style='color: red;'>❌ Tabela não existe</p>";
            continue;
        } else {
            echo "<p style='color: green;'>✅ Tabela existe</p>";
        }
        
        // Verificar colunas
        $stmt = $pdo->query("SHOW COLUMNS FROM $tabela");
        $colunas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<ul>";
        foreach($colunas as $coluna) {
            if(in_array($coluna, $colunas_existentes)) {
                echo "<li style='color: green;'>✅ $coluna</li>";
            } else {
                $problemas[] = "❌ Coluna '$coluna' não existe na tabela '$tabela'";
                echo "<li style='color: red;'>❌ $coluna (não existe)</li>";
            }
        }
        echo "</ul>";
        
    } catch(Exception $e) {
        $problemas[] = "❌ Erro ao verificar tabela '$tabela': " . $e->getMessage();
        echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Resumo</h2>";
if(empty($problemas)) {
    echo "<p style='color: green; font-weight: bold;'>✅ Todas as tabelas e colunas necessárias estão presentes!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Problemas encontrados:</p>";
    echo "<ul>";
    foreach($problemas as $problema) {
        echo "<li style='color: red;'>$problema</li>";
    }
    echo "</ul>";
    echo "<p><strong>Solução:</strong> Execute o arquivo <code>estrutura_banco_almoxarifado.sql</code> no seu banco de dados.</p>";
}
?>
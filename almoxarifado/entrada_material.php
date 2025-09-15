<?php
// entrada_material.php - Página para registrar entrada de materiais
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Inicializar variáveis do formulário
$material_id = '';
$nota_fiscal = '';
$quantidade = '';
$valor_unitario = '';
$data_entrada = date('Y-m-d');

// Verificar se foi passada uma nota fiscal via GET
if (isset($_GET['nota']) && !empty($_GET['nota'])) {
    $nota_fiscal = trim($_GET['nota']);
}

// Buscar todos os materiais para o select
$sql_materiais = "SELECT id, codigo, nome FROM almoxarifado_materiais ORDER BY nome ASC";
$stmt_materiais = $pdo->prepare($sql_materiais);
$stmt_materiais->execute();
$materiais = $stmt_materiais->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_entrada'])){
    $nota_fiscal = trim($_POST["nota_fiscal"]);
    $data_entrada = trim($_POST["data_entrada"]);
    $itens = isset($_POST["itens"]) ? $_POST["itens"] : [];
    
    // Validação
    if(empty($nota_fiscal)){
        $error = "Por favor, informe a nota fiscal.";
    } elseif(empty($data_entrada)){
        $error = "Por favor, informe a data de entrada.";
    } elseif(empty($itens) || !is_array($itens)){
        $error = "Por favor, adicione pelo menos um item.";
    } else {
        // Verificar se a nota fiscal existe
        // Primeiro verificar se a coluna 'saldo' existe na tabela
        $colunas_existentes = array();
        try {
            $stmt_colunas = $pdo->prepare("SHOW COLUMNS FROM notas_fiscais LIKE 'saldo'");
            $stmt_colunas->execute();
            $colunas_existentes = $stmt_colunas->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignorar erros na verificação de colunas
        }
        
        // Montar a query com base na existência da coluna 'saldo'
        if (count($colunas_existentes) > 0) {
            // Coluna 'saldo' existe
            $sql_verifica_nota = "SELECT nota_numero, nota_valor, saldo FROM notas_fiscais WHERE nota_numero = ?";
            $stmt_verifica_nota = $pdo->prepare($sql_verifica_nota);
            $stmt_verifica_nota->execute([$nota_fiscal]);
            $nota_fiscal_info = $stmt_verifica_nota->fetch(PDO::FETCH_ASSOC);
        } else {
            // Coluna 'saldo' não existe
            $sql_verifica_nota = "SELECT nota_numero, nota_valor FROM notas_fiscais WHERE nota_numero = ?";
            $stmt_verifica_nota = $pdo->prepare($sql_verifica_nota);
            $stmt_verifica_nota->execute([$nota_fiscal]);
            $nota_fiscal_info = $stmt_verifica_nota->fetch(PDO::FETCH_ASSOC);
            // Adicionar a coluna 'saldo' ao array para manter a compatibilidade
            if ($nota_fiscal_info) {
                $nota_fiscal_info['saldo'] = $nota_fiscal_info['nota_valor'];
            }
        }
        
        if(!$nota_fiscal_info){
            $error = "Nota fiscal não encontrada. Por favor, verifique se a nota fiscal está cadastrada.";
        } else {
            // Calcular valor total das entradas
            $valor_total_entradas = 0;
            foreach($itens as $item) {
                $quantidade = isset($item['quantidade']) ? (float)$item['quantidade'] : 0;
                $valor_unitario = isset($item['valor_unitario']) ? (float)$item['valor_unitario'] : 0;
                $valor_total_entradas += $quantidade * $valor_unitario;
            }
            
            // Verificar se há saldo suficiente na nota fiscal
            $saldo_disponivel = isset($nota_fiscal_info['saldo']) ? $nota_fiscal_info['saldo'] : $nota_fiscal_info['nota_valor'];
            
            if($saldo_disponivel < $valor_total_entradas){
                $error = "Saldo insuficiente na nota fiscal. Saldo disponível: R$ " . number_format($saldo_disponivel, 2, ',', '.') . ". Valor total das entradas: R$ " . number_format($valor_total_entradas, 2, ',', '.');
            } else {
                try {
                    // Iniciar transação
                    $pdo->beginTransaction();
                    
                    // Processar cada item
                    foreach($itens as $index => $item) {
                        $material_id = trim($item["material_id"]);
                        $quantidade = trim($item["quantidade"]);
                        $valor_unitario = trim($item["valor_unitario"]);
                        
                        // Validar item
                        if(empty($material_id)){
                            throw new Exception("Item #" . ($index+1) . ": Por favor, selecione um material.");
                        } elseif(empty($quantidade) || !is_numeric($quantidade) || $quantidade <= 0){
                            throw new Exception("Item #" . ($index+1) . ": Quantidade deve ser um número positivo.");
                        } elseif(empty($valor_unitario) || !is_numeric($valor_unitario) || $valor_unitario < 0){
                            throw new Exception("Item #" . ($index+1) . ": Valor unitário deve ser um número positivo.");
                        }
                        
                        // 1. Inserir registro na tabela de entradas
                        $sql_entrada = "INSERT INTO almoxarifado_entradas (material_id, quantidade, valor_unitario, nota_fiscal, data_entrada, data_cadastro, usuario_id) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt_entrada = $pdo->prepare($sql_entrada);
                        $stmt_entrada->execute([$material_id, $quantidade, $valor_unitario, $nota_fiscal, $data_entrada, date('Y-m-d H:i:s'), $_SESSION['id']]);
                        $entrada_id = $pdo->lastInsertId();
                        
                        // 2. Atualizar estoque do material
                        $sql_estoque = "UPDATE almoxarifado_materiais SET estoque_atual = estoque_atual + ? WHERE id = ?";
                        $stmt_estoque = $pdo->prepare($sql_estoque);
                        $stmt_estoque->execute([$quantidade, $material_id]);
                        
                        // 3. Registrar movimentação
                        // Buscar saldo anterior
                        $sql_saldo = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
                        $stmt_saldo = $pdo->prepare($sql_saldo);
                        $stmt_saldo->execute([$material_id]);
                        $saldo_anterior = $stmt_saldo->fetchColumn();
                        
                        // Calcular novo saldo
                        $saldo_atual = $saldo_anterior + $quantidade;
                        
                        $sql_movimentacao = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) 
                                             VALUES (?, 'entrada', ?, ?, ?, ?, ?, ?)";
                        $stmt_movimentacao = $pdo->prepare($sql_movimentacao);
                        $stmt_movimentacao->execute([$material_id, $quantidade, $saldo_anterior, $saldo_atual, date('Y-m-d H:i:s'), $_SESSION['id'], $entrada_id]);
                    }
                    
                    // 4. Descontar do saldo da nota fiscal (apenas se a coluna existir)
                    // Verificar se a coluna 'saldo' existe na tabela
                    $colunas_nota_fiscal = array_keys($nota_fiscal_info);
                    if(in_array('saldo', $colunas_nota_fiscal)) {
                        $novo_saldo_nota = $nota_fiscal_info['saldo'] - $valor_total_entradas;
                        $sql_atualiza_saldo_nota = "UPDATE notas_fiscais SET saldo = ? WHERE nota_numero = ?";
                        $stmt_atualiza_saldo_nota = $pdo->prepare($sql_atualiza_saldo_nota);
                        $stmt_atualiza_saldo_nota->execute([$novo_saldo_nota, $nota_fiscal]);
                    }
                    
                    // Confirmar transação
                    $pdo->commit();
                    
                    $message = "Todas as entradas foram registradas com sucesso!";
                    
                    // Limpar campos
                    $nota_fiscal = '';
                    $data_entrada = date('Y-m-d');
                    
                } catch (Exception $e) {
                    // Reverter transação em caso de erro
                    $pdo->rollback();
                    $error = "Erro ao registrar entradas: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Registrar Entrada de Material</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Dados da Entrada</h3>
        </div>
        <div class="card-body">
            <form id="entradaForm" action="entrada_material.php" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nota_fiscal">Nota Fiscal:</label>
                            <select class="form-control select_nota_fiscal" id="nota_fiscal" name="nota_fiscal" required style="min-width: 300px;">
                                <option value="">Selecione uma nota fiscal</option>
                                <?php 
                                // Buscar notas fiscais de empenhos abertos
                                $sql_notas_abertas = "SELECT nf.nota_numero, nf.nota_valor, ei.fornecedor, ei.numero as empenho_numero
                                                      FROM notas_fiscais nf
                                                      JOIN empenhos_insumos ei ON nf.empenho_numero = ei.numero
                                                      WHERE ei.status = 'Aberto'
                                                      ORDER BY nf.nota_numero ASC";
                                $stmt_notas_abertas = $pdo->prepare($sql_notas_abertas);
                                $stmt_notas_abertas->execute();
                                $notas_abertas = $stmt_notas_abertas->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach($notas_abertas as $nota): ?>
                                    <option value="<?php echo htmlspecialchars($nota['nota_numero']); ?>" title="<?php echo htmlspecialchars($nota['nota_numero'] . ' - ' . $nota['fornecedor'] . ' (R$ ' . number_format($nota['nota_valor'], 2, ',', '.') . ')'); ?>">
                                        <?php echo htmlspecialchars($nota['nota_numero'] . ' - ' . $nota['fornecedor'] . ' (R$ ' . number_format($nota['nota_valor'], 2, ',', '.') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Apenas notas fiscais de empenhos abertos são exibidas</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="data_entrada">Data de Entrada:</label>
                            <input type="date" class="form-control" id="data_entrada" name="data_entrada" value="<?php echo htmlspecialchars($data_entrada); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div id="itens-entrada">
                    <div class="item-entrada card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="material_id_0">Material:</label>
                                        <select class="form-control material-select" id="material_id_0" name="itens[0][material_id]" required style="min-width: 300px;">
                                            <option value="">Selecione um material</option>
                                            <?php foreach($materiais as $material): ?>
                                                <option value="<?php echo $material['id']; ?>" title="<?php echo htmlspecialchars($material['codigo'] . ' - ' . $material['nome']); ?>">
                                                    <?php echo htmlspecialchars($material['codigo'] . ' - ' . $material['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="quantidade_0">Quantidade:</label>
                                        <input type="number" class="form-control quantidade-input" id="quantidade_0" name="itens[0][quantidade]" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="valor_unitario_0">Valor Unitário (R$):</label>
                                        <input type="number" class="form-control valor-input" id="valor_unitario_0" name="itens[0][valor_unitario]" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="button" id="adicionar-item" class="btn btn-secondary mb-3">
                    <i class="fas fa-plus"></i> Adicionar Mais Itens
                </button>
                
                <div class="form-group">
                    <button type="submit" name="registrar_entrada" class="btn btn-primary">Registrar Todas as Entradas</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    // Buscar últimas entradas registradas
    $sql_ultimas = "SELECT ae.*, am.nome as material_nome, am.codigo as material_codigo 
                    FROM almoxarifado_entradas ae 
                    JOIN almoxarifado_materiais am ON ae.material_id = am.id 
                    ORDER BY ae.data_cadastro DESC 
                    LIMIT 10";
    $stmt_ultimas = $pdo->prepare($sql_ultimas);
    $stmt_ultimas->execute();
    $ultimas_entradas = $stmt_ultimas->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <?php if(!empty($ultimas_entradas)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Últimas Entradas Registradas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Material</th>
                                <th>Nota Fiscal</th>
                                <th>Quantidade</th>
                                <th>Valor Unitário</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ultimas_entradas as $entrada): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($entrada['data_entrada'])); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['material_codigo'] . ' - ' . $entrada['material_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['nota_fiscal']); ?></td>
                                    <td><?php echo number_format($entrada['quantidade'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($entrada['quantidade'] * $entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.suggestions-list {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    width: calc(100% - 2px);
    display: none;
}

.suggestions-list .list-group-item {
    padding: 10px;
    border: none;
    border-bottom: 1px solid #eee;
}

.suggestions-list .list-group-item:last-child {
    border-bottom: none;
}

.suggestions-list .list-group-item:hover {
    background-color: #f8f9fa;
}

.suggestions-list .list-group-item strong {
    display: block;
    margin-bottom: 5px;
}

.suggestions-list .list-group-item small {
    display: block;
    color: #666;
}

/* Estilos para resolver o problema de texto cortado nos selects */
.select_nota_fiscal, .material-select {
    width: 100%;
    min-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Ajuste específico para os options dentro dos selects */
.select_nota_fiscal option, .material-select option {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
</style>

<script>
let itemIndex = 1;

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar novo item
    document.getElementById('adicionar-item').addEventListener('click', function() {
        const itensContainer = document.getElementById('itens-entrada');
        const novoItem = document.createElement('div');
        novoItem.className = 'item-entrada card mb-3';
        novoItem.innerHTML = `
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="material_id_${itemIndex}">Material:</label>
                            <select class="form-control material-select" id="material_id_${itemIndex}" name="itens[${itemIndex}][material_id]" required>
                                <option value="">Selecione um material</option>
                                <?php foreach($materiais as $material): ?>
                                    <option value="<?php echo $material['id']; ?>">
                                        <?php echo htmlspecialchars($material['codigo'] . ' - ' . $material['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="quantidade_${itemIndex}">Quantidade:</label>
                            <input type="number" class="form-control quantidade-input" id="quantidade_${itemIndex}" name="itens[${itemIndex}][quantidade]" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="valor_unitario_${itemIndex}">Valor Unitário (R$):</label>
                            <input type="number" class="form-control valor-input" id="valor_unitario_${itemIndex}" name="itens[${itemIndex}][valor_unitario]" step="0.01" min="0" required>
                            <button type="button" class="btn btn-danger btn-sm mt-2 remover-item">
                                <i class="fas fa-trash"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        itensContainer.appendChild(novoItem);
        itemIndex++;
        
        // Adicionar evento de remoção ao botão recém-criado
        novoItem.querySelector('.remover-item').addEventListener('click', function() {
            if (document.querySelectorAll('.item-entrada').length > 1) {
                novoItem.remove();
            } else {
                alert('Você deve manter pelo menos um item.');
            }
        });
    });
    
    // Adicionar evento de remoção ao primeiro item
    document.querySelector('.remover-item').addEventListener('click', function() {
        if (document.querySelectorAll('.item-entrada').length > 1) {
            this.closest('.item-entrada').remove();
        } else {
            alert('Você deve manter pelo menos um item.');
        }
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
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
    $nota_fiscal = isset($_POST["nota_fiscal"]) ? trim($_POST["nota_fiscal"]) : '';
    $data_entrada = isset($_POST["data_entrada"]) ? trim($_POST["data_entrada"]) : '';
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
        $sql_verifica_nota = "SELECT nota_numero, nota_valor, saldo, empenho_numero FROM notas_fiscais WHERE nota_numero = ?";
        $stmt_verifica_nota = $pdo->prepare($sql_verifica_nota);
        $stmt_verifica_nota->execute([$nota_fiscal]);
        $nota_fiscal_info = $stmt_verifica_nota->fetch(PDO::FETCH_ASSOC);
        
        // Se o saldo for NULL, inicializar com o valor da nota
        if ($nota_fiscal_info && $nota_fiscal_info['saldo'] === null) {
            $sql_init_saldo = "UPDATE notas_fiscais SET saldo = nota_valor WHERE nota_numero = ?";
            $stmt_init_saldo = $pdo->prepare($sql_init_saldo);
            $stmt_init_saldo->execute([$nota_fiscal]);
            $nota_fiscal_info['saldo'] = $nota_fiscal_info['nota_valor'];
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
            $saldo_disponivel = $nota_fiscal_info['saldo'];
            
            if($saldo_disponivel < $valor_total_entradas){
                $error = "Saldo insuficiente na nota fiscal. Saldo disponível: R$ " . number_format($saldo_disponivel, 2, ',', '.') . ". Valor total das entradas: R$ " . number_format($valor_total_entradas, 2, ',', '.');
            } else {
                try {
                    // Iniciar transação
                    $pdo->beginTransaction();
                    
                    // Processar cada item
                    foreach($itens as $index => $item) {
                        $material_id = isset($item["material_id"]) ? trim($item["material_id"]) : '';
                        $quantidade = isset($item["quantidade"]) ? trim($item["quantidade"]) : '';
                        $valor_unitario = isset($item["valor_unitario"]) ? trim($item["valor_unitario"]) : '';
                        
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
                        
                        // 2. Atualizar estoque, valor unitário e vincular à nota fiscal
                        $sql_estoque = "UPDATE almoxarifado_materiais SET estoque_atual = estoque_atual + ?, valor_unitario = ?, nota_fiscal = ? WHERE id = ?";
                        $stmt_estoque = $pdo->prepare($sql_estoque);
                        $stmt_estoque->execute([$quantidade, $valor_unitario, $nota_fiscal, $material_id]);
                        
                        // 3. Registrar movimentação
                        // Buscar saldo anterior (antes da atualização)
                        $sql_saldo_anterior = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
                        $stmt_saldo_anterior = $pdo->prepare($sql_saldo_anterior);
                        $stmt_saldo_anterior->execute([$material_id]);
                        $saldo_atual = $stmt_saldo_anterior->fetchColumn(); // Já atualizado
                        $saldo_anterior = $saldo_atual - $quantidade; // Calcular o anterior
                        
                        $sql_movimentacao = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) 
                                             VALUES (?, 'entrada', ?, ?, ?, ?, ?, ?)";
                        $stmt_movimentacao = $pdo->prepare($sql_movimentacao);
                        $stmt_movimentacao->execute([$material_id, $quantidade, $saldo_anterior, $saldo_atual, date('Y-m-d H:i:s'), $_SESSION['id'], $entrada_id]);
                    }
                    
                    // 4. Descontar do saldo da nota fiscal e verificar se deve fechar empenho
                    $novo_saldo_nota = $nota_fiscal_info['saldo'] - $valor_total_entradas;
                    $sql_atualiza_saldo_nota = "UPDATE notas_fiscais SET saldo = ? WHERE nota_numero = ?";
                    $stmt_atualiza_saldo_nota = $pdo->prepare($sql_atualiza_saldo_nota);
                    $stmt_atualiza_saldo_nota->execute([$novo_saldo_nota, $nota_fiscal]);
                    
                    // 5. Verificar se o empenho deve ser fechado (saldo = 0)
                    $sql_check_empenho = "SELECT saldo FROM empenhos_insumos WHERE numero = ?";
                    $stmt_check_empenho = $pdo->prepare($sql_check_empenho);
                    $stmt_check_empenho->execute([$nota_fiscal_info['empenho_numero']]);
                    $saldo_empenho = $stmt_check_empenho->fetchColumn();
                    
                    if($saldo_empenho == 0){
                        $sql_fechar_empenho = "UPDATE empenhos_insumos SET status = 'Fechado' WHERE numero = ?";
                        $stmt_fechar_empenho = $pdo->prepare($sql_fechar_empenho);
                        $stmt_fechar_empenho->execute([$nota_fiscal_info['empenho_numero']]);
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
                                // Buscar notas fiscais com saldo disponível
                                $sql_notas_abertas = "SELECT nf.nota_numero, nf.nota_valor, nf.saldo, nf.fornecedor, ei.numero as empenho_numero
                                                      FROM notas_fiscais nf
                                                      JOIN empenhos_insumos ei ON nf.empenho_numero = ei.numero
                                                      WHERE nf.saldo > 0
                                                      ORDER BY nf.nota_numero ASC";
                                $stmt_notas_abertas = $pdo->prepare($sql_notas_abertas);
                                $stmt_notas_abertas->execute();
                                $notas_abertas = $stmt_notas_abertas->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach($notas_abertas as $nota): ?>
                                    <option value="<?php echo htmlspecialchars($nota['nota_numero']); ?>" title="<?php echo htmlspecialchars($nota['nota_numero'] . ' - ' . $nota['fornecedor'] . ' (Saldo: R$ ' . number_format($nota['saldo'], 2, ',', '.') . ')'); ?>">
                                        <?php echo htmlspecialchars($nota['nota_numero'] . ' - ' . $nota['fornecedor'] . ' (Saldo: R$ ' . number_format($nota['saldo'], 2, ',', '.') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Apenas notas fiscais com saldo disponível são exibidas</small>
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
                                    <div class="form-group" style="position: relative;">
                                        <label for="material_search_0">Material:</label>
                                        <input type="text" class="form-control material-search" id="material_search_0" placeholder="Digite para buscar material..." autocomplete="off" required>
                                        <input type="hidden" name="itens[0][material_id]" id="material_id_0" required>
                                        <div id="material_suggestions_0" class="suggestions-list"></div>
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.suggestion-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
}

.suggestion-item strong {
    color: #007bff;
    margin-right: 5px;
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

const baseUrl = window.location.origin + '/inventario';

// Função para configurar busca automática de material
function setupMaterialSearch(index) {
    const searchInput = document.getElementById('material_search_' + index);
    const hiddenInput = document.getElementById('material_id_' + index);
    const suggestionsDiv = document.getElementById('material_suggestions_' + index);
    let timeout;
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            timeout = setTimeout(() => {
                fetch(`${baseUrl}/api/search_materiais.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsDiv.innerHTML = '';
                        if (data.success && data.materiais && data.materiais.length > 0) {
                            data.materiais.forEach(material => {
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.innerHTML = `<strong>${material.codigo}</strong> - ${material.nome}`;
                                div.addEventListener('click', () => {
                                    searchInput.value = `${material.codigo} - ${material.nome}`;
                                    hiddenInput.value = material.id;
                                    suggestionsDiv.style.display = 'none';
                                });
                                suggestionsDiv.appendChild(div);
                            });
                            suggestionsDiv.style.display = 'block';
                        } else {
                            suggestionsDiv.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Erro na busca:', error);
                        suggestionsDiv.style.display = 'none';
                    });
            }, 300);
        } else {
            suggestionsDiv.style.display = 'none';
            hiddenInput.value = '';
        }
    });
    
    // Esconder sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Configurar busca para o primeiro item
    setupMaterialSearch(0);
    
    // Adicionar novo item
    document.getElementById('adicionar-item').addEventListener('click', function() {
        const itensContainer = document.getElementById('itens-entrada');
        const novoItem = document.createElement('div');
        novoItem.className = 'item-entrada card mb-3';
        novoItem.innerHTML = `
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group" style="position: relative;">
                            <label for="material_search_${itemIndex}">Material:</label>
                            <input type="text" class="form-control material-search" id="material_search_${itemIndex}" placeholder="Digite para buscar material..." autocomplete="off" required>
                            <input type="hidden" name="itens[${itemIndex}][material_id]" id="material_id_${itemIndex}" required>
                            <div id="material_suggestions_${itemIndex}" class="suggestions-list"></div>
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
        
        // Configurar busca automática para o novo item
        setupMaterialSearch(itemIndex);
        
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
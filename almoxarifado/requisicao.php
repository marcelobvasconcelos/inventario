<?php
// almoxarifado/requisicao.php - Formulário de requisição de produtos
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Verificar se o usuário tem permissão de administrador, almoxarife, visualizador ou gestor
if ($_SESSION["permissao"] != 'Administrador' && $_SESSION["permissao"] != 'Almoxarife' && $_SESSION["permissao"] != 'Visualizador' && $_SESSION["permissao"] != 'Gestor') {
    header("location: index.php");
    exit;
}

// Buscar todos os produtos disponíveis
$sql_produtos = "SELECT id, nome, estoque_atual, unidade_medida FROM almoxarifado_produtos WHERE estoque_atual > 0 ORDER BY nome";
$produtos = [];
if($stmt_produtos = mysqli_prepare($link, $sql_produtos)){
    if(mysqli_stmt_execute($stmt_produtos)){
        $result_produtos = mysqli_stmt_get_result($stmt_produtos);
        while($row = mysqli_fetch_assoc($result_produtos)){
            $produtos[] = $row;
        }
    }
    mysqli_stmt_close($stmt_produtos);
}

// Buscar todos os locais
$sql_locais = "SELECT id, nome FROM locais ORDER BY nome";
$locais = [];
if($stmt_locais = mysqli_prepare($link, $sql_locais)){
    if(mysqli_stmt_execute($stmt_locais)){
        $result_locais = mysqli_stmt_get_result($stmt_locais);
        while($row = mysqli_fetch_assoc($result_locais)){
            $locais[] = $row;
        }
    }
    mysqli_stmt_close($stmt_locais);
}

// Variáveis para mensagens
$mensagem = "";
$mensagem_tipo = "";

// Processar o formulário quando for enviado
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Obter dados do formulário
    $local_id = (int)$_POST['local_id'];
    $justificativa = trim($_POST['justificativa']);
    
    // Validar dados
    if(empty($local_id)){
        $mensagem = "Por favor, selecione um local.";
        $mensagem_tipo = "error";
    } elseif(empty($justificativa)){
        $mensagem = "Por favor, informe a justificativa da requisição.";
        $mensagem_tipo = "error";
    } else {
        // Iniciar transação
        mysqli_autocommit($link, FALSE);
        
        try {
            // Inserir a requisição
            $sql_requisicao = "INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa, status_notificacao) VALUES (?, ?, NOW(), ?, 'pendente')";
            if($stmt_requisicao = mysqli_prepare($link, $sql_requisicao)){
                mysqli_stmt_bind_param($stmt_requisicao, "iis", $_SESSION['id'], $local_id, $justificativa);
                if(mysqli_stmt_execute($stmt_requisicao)){
                    $requisicao_id = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt_requisicao);
                    
                    // Processar os itens da requisição
                    $itens_inseridos = false;
                    foreach($_POST['produto_id'] as $index => $produto_id){
                        $quantidade = (int)$_POST['quantidade'][$index];
                        
                        // Verificar se a quantidade é maior que zero
                        if($quantidade > 0){
                            // Inserir item da requisição
                            $sql_item = "INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) VALUES (?, ?, ?)";
                            if($stmt_item = mysqli_prepare($link, $sql_item)){
                                mysqli_stmt_bind_param($stmt_item, "iii", $requisicao_id, $produto_id, $quantidade);
                                if(mysqli_stmt_execute($stmt_item)){
                                    $itens_inseridos = true;
                                } else {
                                    throw new Exception("Erro ao inserir item da requisição.");
                                }
                                mysqli_stmt_close($stmt_item);
                            } else {
                                throw new Exception("Erro ao preparar statement para item da requisição.");
                            }
                        }
                    }
                    
                    if($itens_inseridos){
                        // Após inserir a requisição, criar notificação para os administradores
                        // Buscar todos os usuários com permissão de administrador
                        $sql_admins = "SELECT id FROM usuarios WHERE permissao = 'Administrador' AND status = 'aprovado'";
                        $result_admins = mysqli_query($link, $sql_admins);
                        
                        if($result_admins && mysqli_num_rows($result_admins) > 0){
                            // Criar uma notificação para cada administrador
                            while($admin = mysqli_fetch_assoc($result_admins)){
                                $admin_id = $admin['id'];
                                
                                // Mensagem da notificação
                                $mensagem_notificacao = "Nova requisição de almoxarifado #" . $requisicao_id . " criada por " . $_SESSION['nome'] . ".";
                                
                                // Inserir notificação
                                $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes 
                                    (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) 
                                    VALUES (?, ?, ?, 'nova_requisicao', ?, 'pendente')";
                                
                                if($stmt_notificacao = mysqli_prepare($link, $sql_notificacao)){
                                    mysqli_stmt_bind_param($stmt_notificacao, "iiis", $requisicao_id, $_SESSION['id'], $admin_id, $mensagem_notificacao);
                                    if(!mysqli_stmt_execute($stmt_notificacao)){
                                        throw new Exception("Erro ao criar notificação para administrador ID: " . $admin_id);
                                    }
                                    mysqli_stmt_close($stmt_notificacao);
                                } else {
                                    throw new Exception("Erro ao preparar statement para notificação.");
                                }
                            }
                        }
                        
                        // Commit da transação
                        mysqli_commit($link);
                        
                        $mensagem = "Requisição criada com sucesso! Aguarde a aprovação do administrador.";
                        $mensagem_tipo = "success";
                        
                        // Limpar os dados do formulário
                        $_POST = array();
                    } else {
                        throw new Exception("Nenhum item válido foi adicionado à requisição.");
                    }
                } else {
                    throw new Exception("Erro ao criar requisição.");
                }
            } else {
                throw new Exception("Erro ao preparar statement para requisição.");
            }
        } catch(Exception $e) {
            // Rollback da transação em caso de erro
            mysqli_rollback($link);
            $mensagem = "Erro ao criar requisição: " . $e->getMessage();
            $mensagem_tipo = "error";
        }
        
        // Reativar o autocommit
        mysqli_autocommit($link, TRUE);
    }
}
?>

<div class="almoxarifado-header">
    <h2>Nova Requisição</h2>
</div>

<?php if(!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $mensagem_tipo; ?>">
        <?php echo $mensagem; ?>
    </div>
<?php endif; ?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="almoxarifado-form-section">
        <h3>Dados da Requisição</h3>
        
        <div class="form-group">
            <label>Local de Destino</label>
            <select name="local_id" class="form-control">
                <option value="">Selecione um local</option>
                <?php foreach($locais as $local): ?>
                    <option value="<?php echo $local['id']; ?>" <?php echo (isset($_POST['local_id']) && $_POST['local_id'] == $local['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($local['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Justificativa</label>
            <textarea name="justificativa" class="form-control" rows="4"><?php echo isset($_POST['justificativa']) ? htmlspecialchars($_POST['justificativa']) : ''; ?></textarea>
        </div>
    </div>
    
    <div class="almoxarifado-form-section">
        <h3>Itens da Requisição</h3>
        
        <div id="itens-requisicao">
            <div class="item-requisicao">
                <div class="form-group">
                    <label>Produto</label>
                    <select name="produto_id[]" class="form-control produto-select">
                        <option value="">Selecione um produto</option>
                        <?php foreach($produtos as $produto): ?>
                            <option value="<?php echo $produto['id']; ?>" data-estoque="<?php echo $produto['estoque_atual']; ?>">
                                <?php echo htmlspecialchars($produto['nome']); ?> (Estoque: <?php echo $produto['estoque_atual']; ?> <?php echo $produto['unidade_medida']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1" value="<?php echo isset($_POST['quantidade'][0]) ? (int)$_POST['quantidade'][0] : ''; ?>">
                    <small class="form-text text-muted estoque-info" style="display: none;">Estoque disponível: <span class="estoque-valor"></span></small>
                </div>
                
                <button type="button" class="btn btn-danger remover-item" style="display: none;">Remover Item</button>
            </div>
        </div>
        
        <button type="button" class="btn btn-secondary" id="adicionar-item">Adicionar Mais Itens</button>
    </div>
    
    <div class="form-group">
        <input type="submit" class="btn-custom" value="Enviar Requisição">
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para adicionar novo item
    document.getElementById('adicionar-item').addEventListener('click', function() {
        var itensContainer = document.getElementById('itens-requisicao');
        var novoItem = document.createElement('div');
        novoItem.className = 'item-requisicao';
        novoItem.innerHTML = `
            <hr>
            <div class="form-group">
                <label>Produto</label>
                <select name="produto_id[]" class="form-control produto-select">
                    <option value="">Selecione um produto</option>
                    <?php foreach($produtos as $produto): ?>
                        <option value="<?php echo $produto['id']; ?>" data-estoque="<?php echo $produto['estoque_atual']; ?>">
                            <?php echo htmlspecialchars($produto['nome']); ?> (Estoque: <?php echo $produto['estoque_atual']; ?> <?php echo $produto['unidade_medida']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Quantidade</label>
                <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1">
                <small class="form-text text-muted estoque-info" style="display: none;">Estoque disponível: <span class="estoque-valor"></span></small>
            </div>
            
            <button type="button" class="btn btn-danger remover-item">Remover Item</button>
        `;
        itensContainer.appendChild(novoItem);
        
        // Adicionar evento para o novo select
        var novoSelect = novoItem.querySelector('.produto-select');
        novoSelect.addEventListener('change', function() {
            mostrarEstoque(this);
        });
        
        // Adicionar evento para o novo input de quantidade
        var novoInputQuantidade = novoItem.querySelector('.quantidade-input');
        novoInputQuantidade.addEventListener('input', function() {
            validarQuantidade(this);
        });
        
        // Adicionar evento para o botão de remover
        var botaoRemover = novoItem.querySelector('.remover-item');
        botaoRemover.addEventListener('click', function() {
            removerItem(this);
        });
        botaoRemover.style.display = 'inline-block';
    });
    
    // Função para mostrar estoque disponível
    function mostrarEstoque(select) {
        var estoqueInfo = select.closest('.item-requisicao').querySelector('.estoque-info');
        var estoqueValor = select.closest('.item-requisicao').querySelector('.estoque-valor');
        
        if(select.value) {
            var estoque = select.options[select.selectedIndex].getAttribute('data-estoque');
            estoqueValor.textContent = estoque;
            estoqueInfo.style.display = 'block';
        } else {
            estoqueInfo.style.display = 'none';
        }
    }
    
    // Função para validar quantidade
    function validarQuantidade(input) {
        var select = input.closest('.item-requisicao').querySelector('.produto-select');
        if(select.value && input.value) {
            var estoque = parseInt(select.options[select.selectedIndex].getAttribute('data-estoque'));
            var quantidade = parseInt(input.value);
            
            if(quantidade > estoque) {
                alert('A quantidade solicitada não pode ser maior que o estoque disponível (' + estoque + ').');
                input.value = estoque;
            }
        }
    }
    
    // Função para remover item
    function removerItem(botao) {
        var item = botao.closest('.item-requisicao');
        item.remove();
    }
    
    // Adicionar eventos para os selects e inputs existentes
    document.querySelectorAll('.produto-select').forEach(function(select) {
        select.addEventListener('change', function() {
            mostrarEstoque(this);
        });
    });
    
    document.querySelectorAll('.quantidade-input').forEach(function(input) {
        input.addEventListener('input', function() {
            validarQuantidade(this);
        });
    });
    
    document.querySelectorAll('.remover-item').forEach(function(botao) {
        botao.addEventListener('click', function() {
            removerItem(this);
        });
        botao.style.display = 'inline-block';
    });
});
</script>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>
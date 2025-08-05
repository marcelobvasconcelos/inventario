<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem adicionar movimentações
if($_SESSION["permissao"] != 'admin'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $item_id = $_POST['item_id'];
    $local_destino_id = $_POST['local_destino_id'];
    $novo_responsavel_id = $_POST['novo_responsavel_id']; // Novo campo
    $usuario_id = $_SESSION['id'];

    // Obter local de origem do item
    $sql_origem = "SELECT local_id FROM itens WHERE id = ?";
    if($stmt_origem = mysqli_prepare($link, $sql_origem)){
        mysqli_stmt_bind_param($stmt_origem, "i", $item_id);
        mysqli_stmt_execute($stmt_origem);
        $result_origem = mysqli_stmt_get_result($stmt_origem);
        $item = mysqli_fetch_assoc($result_origem);
        $local_origem_id = $item['local_id'];
    }

    $sql = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id) VALUES (?, ?, ?, ?)";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "iiii", $item_id, $local_origem_id, $local_destino_id, $usuario_id);
        
        if(mysqli_stmt_execute($stmt)){
            // Atualizar o local e o responsável do item na tabela de itens
            $sql_update = "UPDATE itens SET local_id = ?, responsavel_id = ? WHERE id = ?";
            if($stmt_update = mysqli_prepare($link, $sql_update)){
                mysqli_stmt_bind_param($stmt_update, "iii", $local_destino_id, $novo_responsavel_id, $item_id);
                mysqli_stmt_execute($stmt_update);
            }

            header("location: movimentacoes.php");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
    }
}

$locais = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
$usuarios_ativos = mysqli_query($link, "SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC");

?>

<h2>Registrar Nova Movimentação</h2>

<form action="" method="post">
    <div>
        <label for="local_origem_id">Local de Origem</label>
        <select name="local_origem_id" id="local_origem_id">
            <option value="">Selecione um local</option>
            <?php while($local = mysqli_fetch_assoc($locais)): ?>
                <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div>
        <label for="item_search">Buscar Item (Patrimônio ou Nome)</label>
        <input type="text" id="item_search" placeholder="Comece a digitar...">
        <input type="hidden" name="item_id" id="item_id">
        <div id="item_suggestions" class="suggestions-box"></div>
    </div>

    <div>
        <label for="local_destino_id">Local de Destino</label>
        <select name="local_destino_id" id="local_destino_id">
            <?php 
            // Resetar o ponteiro do resultado para reutilizar $locais
            mysqli_data_seek($locais, 0);
            while($local = mysqli_fetch_assoc($locais)): 
            ?>
                <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div>
        <label for="novo_responsavel_id">Novo Responsável</label>
        <select name="novo_responsavel_id" id="novo_responsavel_id">
            <?php while($usuario = mysqli_fetch_assoc($usuarios_ativos)): ?>
                <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div>
        <input type="submit" value="Registrar Movimentação">
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const localOrigemSelect = document.getElementById('local_origem_id');
    const itemSearchInput = document.getElementById('item_search');
    const itemSuggestionsDiv = document.getElementById('item_suggestions');
    const itemIdInput = document.getElementById('item_id');

    let debounceTimeout;

    function fetchItems(locationId, searchTerm) {
        if (!locationId) {
            itemSuggestionsDiv.innerHTML = '';
            return;
        }

        const url = `api/get_items_by_location.php?location_id=${locationId}&search_term=${searchTerm}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                itemSuggestionsDiv.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.classList.add('suggestion-item');
                        div.textContent = `${item.nome} (${item.patrimonio_novo})`;
                        div.dataset.itemId = item.id;
                        div.addEventListener('click', () => {
                            itemSearchInput.value = `${item.nome} (${item.patrimonio_novo})`;
                            itemIdInput.value = item.id;
                            itemSuggestionsDiv.innerHTML = '';
                        });
                        itemSuggestionsDiv.appendChild(div);
                    });
                } else {
                    itemSuggestionsDiv.innerHTML = '<div class="suggestion-item">Nenhum item encontrado.</div>';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar itens:', error);
                itemSuggestionsDiv.innerHTML = '<div class="suggestion-item">Erro ao carregar itens.</div>';
            });
    }

    localOrigemSelect.addEventListener('change', function() {
        const locationId = this.value;
        const searchTerm = itemSearchInput.value;
        itemIdInput.value = ''; // Limpa o ID do item selecionado
        fetchItems(locationId, searchTerm);
    });

    itemSearchInput.addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        const locationId = localOrigemSelect.value;
        const searchTerm = this.value;
        itemIdInput.value = ''; // Limpa o ID do item selecionado

        debounceTimeout = setTimeout(() => {
            fetchItems(locationId, searchTerm);
        }, 300); // Pequeno atraso para evitar muitas requisições
    });

    // Ocultar sugestões ao clicar fora
    document.addEventListener('click', function(event) {
        if (!itemSearchInput.contains(event.target) && !itemSuggestionsDiv.contains(event.target)) {
            itemSuggestionsDiv.innerHTML = '';
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>
<?php
// Inicia a sessão PHP e inclui o cabeçalho e a conexão com o banco de dados
require_once 'includes/header.php';
require_once 'config/db.php';

// Obtém o ID do local da URL
$local_id = $_GET['id'];

// Busca o nome do local no banco de dados
$sql_local = "SELECT nome FROM locais WHERE id = ?";
if($stmt_local = mysqli_prepare($link, $sql_local)){
    mysqli_stmt_bind_param($stmt_local, "i", $local_id);
    mysqli_stmt_execute($stmt_local);
    $result_local = mysqli_stmt_get_result($stmt_local);
    $local = mysqli_fetch_assoc($result_local);
    $local_nome = $local['nome'];
} else {
    $local_nome = "Local Desconhecido"; // Define um nome padrão se o local não for encontrado
}

// SQL para buscar todos os itens associados a este local
// Inclui informações do item, local e responsável, e o status de confirmação
$sql_itens = "SELECT 
                i.id, i.nome, i.observacao AS descricao, i.patrimonio_novo AS numero_serie, i.data_cadastro AS data_compra, i.estado AS status, 
                l.nome as local_nome, u.nome as responsavel_nome, i.responsavel_id, i.status_confirmacao
              FROM itens i
              LEFT JOIN locais l ON i.local_id = l.id
              LEFT JOIN usuarios u ON i.responsavel_id = u.id
              WHERE i.local_id = ? AND i.estado != 'Excluido'";

// Prepara e executa a consulta SQL para obter os itens
if($stmt_itens = mysqli_prepare($link, $sql_itens)){
    mysqli_stmt_bind_param($stmt_itens, "i", $local_id);
    if(mysqli_stmt_execute($stmt_itens)){
        $itens = mysqli_stmt_get_result($stmt_itens);
    } else {
        echo "Erro ao executar a consulta de itens: " . mysqli_error($link);
        $itens = false; // Define $itens como false para evitar erros no loop de exibição
    }
} else {
    echo "Erro ao preparar a consulta de itens: " . mysqli_error($link);
    $itens = false; // Define $itens como false para evitar erros no loop de exibição
}

?>

<h2>Itens em <?php echo $local_nome; ?></h2>
<p><a href="locais.php" class="btn-custom">Voltar para Locais</a></p>

<?php if($itens && mysqli_num_rows($itens) > 0): // Verifica se há itens para exibir ?>
    <table class="table-striped table-hover">
        <thead>
            <tr>
                <th data-column="id">ID <span class="sort-arrow"></span></th>
                <th data-column="nome">Nome <span class="sort-arrow"></span></th>
                <th data-column="descricao">Descrição <span class="sort-arrow"></span></th>
                <th data-column="Patrimônio">Patrimônio <span class="sort-arrow"></span></th>
                <th data-column="data_compra">Data de Compra <span class="sort-arrow"></span></th>
                <th data-column="status">Status <span class="sort-arrow"></span></th>
                <th data-column="responsavel_nome">Responsável <span class="sort-arrow"></span></th>
                <th>Status Confirmação</th> <!-- Nova coluna para o status de confirmação -->
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($itens)): // Itera sobre cada item ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><a href="item_details.php?id=<?php echo $row['id']; ?>"><?php echo $row['nome']; ?></a></td>
                    <td><?php echo $row['descricao']; ?></td>
                    <td><?php echo $row['numero_serie']; ?></td>
                    <td><?php echo $row['data_compra']; ?></td>
                    <td class="editable-estado" data-item-id="<?php echo $row['id']; ?>" data-estado-atual="<?php echo htmlspecialchars($row['status']); ?>">
                        <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                            <!-- 
                                Elementos para a edição inline do estado:
                                - estado-value: Span que mostra o valor atual do estado
                                - estado-select: Select para escolher o novo valor do estado
                                - edit-icon: Ícone de edição (lápis) que aparece ao passar o mouse
                            -->
                            <span class="estado-value"><?php echo $row['status']; ?></span>
                            <select class="estado-select" style="display: none;">
                                <option value="Em uso" <?php echo ($row['status'] == 'Em uso') ? 'selected' : ''; ?>>Em uso</option>
                                <option value="Ocioso" <?php echo ($row['status'] == 'Ocioso') ? 'selected' : ''; ?>>Ocioso</option>
                                <option value="Recuperável" <?php echo ($row['status'] == 'Recuperável') ? 'selected' : ''; ?>>Recuperável</option>
                                <option value="Inservível" <?php echo ($row['status'] == 'Inservível') ? 'selected' : ''; ?>>Inservível</option>
                            </select>
                            <span class="edit-icon" style="display: none;">✏️</span>
                        <?php else: ?>
                            <?php echo $row['status']; ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['responsavel_nome']; ?></td>
                    <td>
                        <?php
                            // Lógica para exibir o badge de status de confirmação
                            $status_confirmacao = $row['status_confirmacao'];
                            $badge_class = '';
                            if ($status_confirmacao == 'Pendente') {
                                $badge_class = 'badge-warning'; // Amarelo para pendente
                            } elseif ($status_confirmacao == 'Confirmado') {
                                $badge_class = 'badge-success'; // Verde para confirmado
                            } elseif ($status_confirmacao == 'Nao Confirmado') {
                                $badge_class = 'badge-danger'; // Vermelho para não confirmado
                            }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status_confirmacao); ?></span>
                    </td>
                    <td>
                        <?php 
                        // Lógica para exibir botões de ação (Editar/Excluir) com base na permissão
                        if($_SESSION['permissao'] == 'Administrador' || ($_SESSION['permissao'] == 'Gestor' && $row['responsavel_id'] == $_SESSION['id'])): 
                        ?>
                            <a href="item_edit.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                            <?php if($_SESSION['permissao'] == 'Administrador'): // Apenas Administradores podem excluir ?>
                                <a href="item_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este item?');"><i class="fas fa-trash"></i></a>
                            <?php else: // Gestores não podem excluir, ícone desativado ?>
                                <i class="fas fa-trash disabled-icon" title="Permissão negada para excluir"></i>
                            <?php endif; ?>
                        <?php elseif($_SESSION['permissao'] == 'Visualizador'): // Visualizadores não podem editar nem excluir ?>
                            <i class="fas fa-edit disabled-icon" title="Permissão negada para editar"></i>
                            <i class="fas fa-trash disabled-icon" title="Permissão negada para excluir"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: // Se não houver itens, exibe uma mensagem ?>
    <p>Nenhum item encontrado neste local.</p>
<?php endif; ?>

<script>
// JavaScript para funcionalidade de ordenação de tabela (se aplicável)
document.addEventListener('DOMContentLoaded', function() {
    // --- Lógica de ordenação de colunas ---
    const sortableHeaders = document.querySelectorAll('th[data-column]');
    let currentSortColumn = null;
    let currentSortDirection = 'asc';
    
    // Função para obter o valor da célula
    function getCellValue(tr, idx) {
        const cell = tr.children[idx];
        // Se for um link, retorna o texto do link
        const link = cell.querySelector('a');
        if (link) {
            return link.textContent || link.innerText || '';
        }
        // Se for um elemento com classe badge, retorna o texto do badge
        const badge = cell.querySelector('.badge');
        if (badge) {
            return badge.textContent || badge.innerText || '';
        }
        // Caso contrário, retorna o conteúdo da célula
        return cell.textContent || cell.innerText || '';
    }
    
    // Função de comparação
    function comparer(idx, asc) {
        return function(a, b) {
            const v1 = getCellValue(asc ? a : b, idx);
            const v2 = getCellValue(asc ? b : a, idx);
            
            // Verificar se são números
            const num1 = parseFloat(v1);
            const num2 = parseFloat(v2);
            
            // Se ambos forem números válidos, comparar como números
            if (!isNaN(num1) && !isNaN(num2)) {
                return num1 - num2;
            }
            
            // Caso contrário, comparar como strings
            return v1.toString().localeCompare(v2.toString());
        };
    }
    
    sortableHeaders.forEach(header => {
        // Adicionar seta padrão para todas as colunas
        const arrow = header.querySelector('.sort-arrow');
        if (arrow) {
            arrow.classList.add('none');
        }
        
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            const table = header.closest('table');
            const tbody = table.querySelector('tbody');
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            
            // Remover classes de ordenação de todas as setas
            document.querySelectorAll('.sort-arrow').forEach(arrow => {
                arrow.classList.remove('up', 'down');
                arrow.classList.add('none');
            });
            
            // Determinar a direção da ordenação
            if (currentSortColumn === columnIndex) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortDirection = 'asc';
                currentSortColumn = columnIndex;
            }
            
            // Atualizar a seta da coluna clicada
            const clickedArrow = header.querySelector('.sort-arrow');
            if (clickedArrow) {
                clickedArrow.classList.remove('none');
                if (currentSortDirection === 'asc') {
                    clickedArrow.classList.add('up');
                } else {
                    clickedArrow.classList.add('down');
                }
            }
            
            // Ordenar as linhas
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(comparer(columnIndex, currentSortDirection === 'asc'));
            
            // Reordenar as linhas no tbody
            rows.forEach(row => tbody.appendChild(row));
        });
    });
    
    // --- Lógica de Edição Inline do Estado ---
    // Esta seção implementa a funcionalidade que permite ao administrador
    // editar o estado de um item diretamente na tabela, sem precisar
    // acessar a página de edição do item.
    // A implementação é idêntica à da página itens.php
    <?php if($_SESSION['permissao'] == 'Administrador'): ?>
    // Seleciona todas as células da tabela que contêm o estado do item
    const editableEstados = document.querySelectorAll('.editable-estado');
    
    // Para cada célula, configura os event listeners para a edição inline
    editableEstados.forEach(cell => {
        // Elementos dentro da célula
        const estadoValue = cell.querySelector('.estado-value'); // Span que mostra o valor atual
        const estadoSelect = cell.querySelector('.estado-select'); // Select para escolher o novo valor
        const editIcon = cell.querySelector('.edit-icon'); // Ícone de edição (lápis)
        
        // Mostrar o ícone de edição ao passar o mouse sobre a célula
        // Isso fornece uma dica visual de que o campo é editável
        cell.addEventListener('mouseenter', () => {
            editIcon.style.display = 'inline';
        });
        
        // Esconder o ícone de edição ao retirar o mouse da célula
        // Mas apenas se não estiver em modo de edição
        cell.addEventListener('mouseleave', () => {
            if (estadoSelect.style.display === 'none') {
                editIcon.style.display = 'none';
            }
        });
        
        // Clique no valor do estado para iniciar a edição
        // Esconde o texto e mostra o select e o ícone de edição
        estadoValue.addEventListener('click', () => {
            estadoValue.style.display = 'none';
            estadoSelect.style.display = 'inline';
            editIcon.style.display = 'none';
            estadoSelect.focus(); // Coloca o foco no select para facilitar a seleção
        });
        
        // Clique fora do select para finalizar a edição
        // Se o usuário clicar em qualquer lugar da página fora da célula de edição,
        // a edição será finalizada
        document.addEventListener('click', (event) => {
            if (!cell.contains(event.target) && estadoSelect.style.display === 'inline') {
                finishEditing(cell, estadoValue, estadoSelect);
            }
        });
        
        // Pressionar Enter no select para finalizar a edição
        // Oferece uma alternativa ao clique fora para confirmar a edição
        estadoSelect.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                finishEditing(cell, estadoValue, estadoSelect);
            }
        });
    });
    
    /**
     * Função para finalizar a edição do estado do item
     * 
     * Esta função coleta o novo valor selecionado, envia uma requisição AJAX
     * para atualizar o banco de dados e atualiza a interface do usuário com
     * o novo valor.
     * 
     * @param {HTMLElement} cell - A célula da tabela que está sendo editada
     * @param {HTMLElement} estadoValue - O elemento span que mostra o valor do estado
     * @param {HTMLElement} estadoSelect - O elemento select para escolher o novo estado
     */
    function finishEditing(cell, estadoValue, estadoSelect) {
        // Obter o ID do item e o novo estado selecionado
        const itemId = cell.getAttribute('data-item-id');
        const novoEstado = estadoSelect.value;
        const estadoAtual = cell.getAttribute('data-estado-atual');
        // Obter o ícone de edição
        const editIcon = cell.querySelector('.edit-icon');
        
        // Se o valor não mudou, apenas voltar ao modo de visualização
        // Isso evita requisições desnecessárias ao servidor
        if (novoEstado === estadoAtual) {
            estadoValue.style.display = 'inline';
            estadoSelect.style.display = 'none';
            editIcon.style.display = 'none';
            return;
        }
        
        // Enviar requisição AJAX para atualizar o estado
        // Utiliza fetch API para enviar os dados de forma assíncrona
        // Note que o caminho para o endpoint da API é relativo ao diretório atual
        fetch('../api/update_estado_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            // Codifica os dados para envio no formato application/x-www-form-urlencoded
            body: `item_id=${encodeURIComponent(itemId)}&novo_estado=${encodeURIComponent(novoEstado)}`
        })
        .then(response => response.json()) // Converte a resposta para JSON
        .then(result => {
            if (result.success) {
                // Sucesso: Atualizar a interface com o novo valor
                estadoValue.textContent = novoEstado;
                cell.setAttribute('data-estado-atual', novoEstado);
                alert('Estado atualizado com sucesso!');
            } else {
                // Erro: Reverter para o valor anterior e mostrar mensagem de erro
                estadoSelect.value = estadoAtual;
                alert('Erro ao atualizar o estado: ' + result.message);
            }
            
            // Voltar ao modo de visualização
            estadoValue.style.display = 'inline';
            estadoSelect.style.display = 'none';
            editIcon.style.display = 'none';
        })
        .catch(error => {
            // Erro de rede ou outro erro inesperado
            console.error('Erro:', error);
            // Reverter para o valor anterior
            estadoSelect.value = estadoAtual;
            alert('Ocorreu um erro ao tentar atualizar o estado.');
            
            // Voltar ao modo de visualização
            estadoValue.style.display = 'inline';
            estadoSelect.style.display = 'none';
            editIcon.style.display = 'none';
        });
    }
    <?php endif; ?>
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>
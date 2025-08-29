<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'Administrador'){
    echo "Acesso negado.";
    exit;
}

// Variável para armazenar a senha temporária gerada
$senha_temporaria_gerada = "";

// Processar ação de gerar senha temporária
if(isset($_POST['gerar_senha_temporaria']) && isset($_POST['usuario_id'])){
    $usuario_id = $_POST['usuario_id'];
    
    // Gerar uma senha temporária aleatória
    $senha_temporaria = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
    
    // Atualizar a senha do usuário e marcar como senha temporária
    $sql_update = "UPDATE usuarios SET senha = ?, senha_temporaria = 1 WHERE id = ?";
    if($stmt_update = mysqli_prepare($link, $sql_update)){
        mysqli_stmt_bind_param($stmt_update, "si", $senha_hash, $usuario_id);
        if(mysqli_stmt_execute($stmt_update)){
            // Armazenar a senha temporária para exibir
            $senha_temporaria_gerada = $senha_temporaria;
        } else {
            $mensagem = "Erro ao gerar senha temporária.";
        }
        mysqli_stmt_close($stmt_update);
    }
}

// Lógica para aprovar/rejeitar usuários
if(isset($_GET['acao']) && isset($_GET['id'])){
    $acao = $_GET['acao'];
    $usuario_id = $_GET['id'];

    if($acao == 'aprovar'){
        $novo_status = 'aprovado';
    } elseif($acao == 'rejeitar'){
        $novo_status = 'rejeitado';
    } elseif($acao == 'pendente'){
        $novo_status = 'pendente';
    }

    if(isset($novo_status)){
        $update_sql = "UPDATE usuarios SET status = ? WHERE id = ?";
        if($stmt = mysqli_prepare($link, $update_sql)){
            $refs = [];
            $params_update = ["si", $novo_status, $usuario_id];
            foreach($params_update as $key => $value)
                $refs[$key] = &$params_update[$key];
            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
            if(mysqli_stmt_execute($stmt)){
                header("location: usuarios.php"); // Redireciona para limpar a URL
                exit();
            } else {
                echo "Erro ao atualizar o status do usuário.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Consultas para os diferentes status de usuários
$sql_pendentes = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome 
                  FROM usuarios u 
                  JOIN perfis p ON u.permissao_id = p.id 
                  WHERE u.status = 'pendente' AND u.nome != 'Lixeira' 
                  ORDER BY u.nome ASC";
$result_pendentes = mysqli_query($link, $sql_pendentes);
$count_pendentes = mysqli_num_rows($result_pendentes);

$sql_rejeitados = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome 
                   FROM usuarios u 
                   JOIN perfis p ON u.permissao_id = p.id 
                   WHERE u.status = 'rejeitado' AND u.nome != 'Lixeira' 
                   ORDER BY u.nome ASC";
$result_rejeitados = mysqli_query($link, $sql_rejeitados);

$sql_aprovados = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome 
                  FROM usuarios u 
                  JOIN perfis p ON u.permissao_id = p.id 
                  WHERE u.status = 'aprovado' AND u.nome != 'Lixeira' 
                  ORDER BY u.nome ASC";
$result_aprovados = mysqli_query($link, $sql_aprovados);
?>

<h2>Gerenciar Usuários</h2>

<!-- Barra de ferramentas com botão de adicionar e pesquisa -->
<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
    <a href="usuario_add.php" class="btn-custom" title="Adicionar Novo Usuário" style="padding: 10px 15px;">
        <i class="fas fa-user-plus"></i>
    </a>
    
    <!-- Campo de pesquisa -->
    <div style="flex: 1; max-width: 400px;">
        <input type="text" id="pesquisa-usuario" placeholder="Pesquisar usuários (digite pelo menos 3 letras)" style="padding: 10px; width: 100%; border: 1px solid #ddd; border-radius: 5px;">
    </div>
    
    <button id="limpar-pesquisa" class="btn-custom" title="Limpar pesquisa" style="padding: 10px 15px;">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Container para resultados da pesquisa -->
<div id="resultados-pesquisa" style="display: none; margin: 20px 0;">
    <h3>Resultados da Pesquisa</h3>
    <div id="lista-resultados" class="user-list"></div>
</div>

<style>
.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: .25rem;
}

.alert-success strong {
    font-size: 1.2em;
}

.alert-success span {
    display: block;
    margin: 10px 0;
    font-size: 1.5em;
    font-weight: bold;
    color: #d9534f;
    font-family: 'Courier New', monospace;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border: 1px dashed #ccc;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
    line-height: 1.5;
    border-radius: 3px;
}

.btn-aprovar, .btn-rejeitar, .btn-editar, .btn-excluir, .btn-pendente, .btn-warning {
    margin: 2px;
}

/* Estilos para as seções de usuários */
.user-section {
    margin-top: 30px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.user-section h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.user-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.user-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
    min-width: 250px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php if(!empty($senha_temporaria_gerada)): ?>
    <div class="alert alert-success">
        <strong>Senha temporária gerada com sucesso!</strong><br>
        <span><?php echo $senha_temporaria_gerada; ?></span><br>
        <small>Copie esta senha e envie para o usuário. Ela será válida apenas para o primeiro acesso.</small>
    </div>
<?php endif; ?>

<?php if(isset($_GET['status']) && $_GET['status'] == 'usuario_rejeitado'): ?>
    <div class="alert alert-success">
        <strong>Usuário rejeitado com sucesso!</strong><br>
        <small>O usuário foi movido para a seção de usuários rejeitados.</small>
    </div>
<?php endif; ?>

<?php if(isset($_GET['status']) && $_GET['status'] == 'usuario_excluido'): ?>
    <div class="alert alert-success">
        <strong>Usuário excluído com sucesso!</strong><br>
        <small>O usuário foi excluído permanentemente do sistema.</small>
    </div>
<?php endif; ?>

<?php
// Consulta para usuários pendentes
$sql_pendentes = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome 
                  FROM usuarios u 
                  JOIN perfis p ON u.permissao_id = p.id 
                  WHERE u.status = 'pendente' AND u.nome != 'Lixeira' 
                  ORDER BY u.nome ASC";
$result_pendentes = mysqli_query($link, $sql_pendentes);

// Consulta para usuários rejeitados
$sql_rejeitados = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome 
                   FROM usuarios u 
                   JOIN perfis p ON u.permissao_id = p.id 
                   WHERE u.status = 'rejeitado' AND u.nome != 'Lixeira' 
                   ORDER BY u.nome ASC";
$result_rejeitados = mysqli_query($link, $sql_rejeitados);

// Consulta para demais usuários (aprovados)
$sql_aprovados = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome 
                  FROM usuarios u 
                  JOIN perfis p ON u.permissao_id = p.id 
                  WHERE u.status = 'aprovado' AND u.nome != 'Lixeira' 
                  ORDER BY u.nome ASC";
$result_aprovados = mysqli_query($link, $sql_aprovados);
?>

<?php if ($count_pendentes > 0): ?>
    <!-- PENDENTES DE ACEITAÇÃO (no topo quando há pendências) -->
    <div class="user-section">
        <h3>PENDENTES DE ACEITAÇÃO</h3>
        <div class="user-list">
            <?php if ($result_pendentes && mysqli_num_rows($result_pendentes) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_pendentes)): ?>
                    <?php
                    // Definir cor do perfil
                    $cor_perfil = '#95a5a6'; // Cor padrão
                    switch($row['perfil_nome']) {
                        case 'Administrador':
                            $cor_perfil = '#ff6b6b';
                            break;
                        case 'Gestor':
                            $cor_perfil = '#4ecdc4';
                            break;
                        case 'Visualizador':
                            $cor_perfil = '#45b7d1';
                            break;
                    }
                    ?>
                    <div class="user-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong><?php echo htmlspecialchars($row['nome']); ?></strong><br>
                                <small>ID: <?php echo $row['id']; ?></small><br>
                                <small><?php echo htmlspecialchars($row['email']); ?></small><br>
                                <a href="usuario_itens.php?id=<?php echo $row['id']; ?>" style="color: #3498db; text-decoration: none; font-size: 0.9em;">
                                    <i class="fas fa-box"></i> Itens do usuário
                                </a>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <span class="badge" style="background-color: <?php echo $cor_perfil; ?>;"><?php echo htmlspecialchars($row['perfil_nome']); ?></span>
                            <span class="badge" style="background-color: #ffc107; color: white;">Pendente</span>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="usuarios.php?acao=aprovar&id=<?php echo $row['id']; ?>" class="btn btn-aprovar btn-sm" title="Aprovar">
                                <i class="fas fa-check"></i> Aprovar
                            </a>
                            <a href="usuarios.php?acao=rejeitar&id=<?php echo $row['id']; ?>" class="btn btn-rejeitar btn-sm" title="Rejeitar">
                                <i class="fas fa-times"></i> Rejeitar
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Nenhum usuário pendente encontrado.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- USUÁRIOS APROVADOS -->
<div class="user-section">
    <h3>USUÁRIOS APROVADOS</h3>
    <div class="user-list">
        <?php if ($result_aprovados && mysqli_num_rows($result_aprovados) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result_aprovados)): ?>
                <!-- Consulta para verificar solicitações pendentes -->
                <?php
                $sql_solicitacoes = "SELECT COUNT(*) as solicitacoes_pendentes FROM solicitacoes_senha WHERE usuario_id = ? AND status = 'pendente'";
                $stmt_solicitacoes = mysqli_prepare($link, $sql_solicitacoes);
                mysqli_stmt_bind_param($stmt_solicitacoes, "i", $row['id']);
                mysqli_stmt_execute($stmt_solicitacoes);
                $result_solicitacoes = mysqli_stmt_get_result($stmt_solicitacoes);
                $solicitacoes = mysqli_fetch_assoc($result_solicitacoes);
                $solicitacoes_pendentes = $solicitacoes['solicitacoes_pendentes'];
                mysqli_stmt_close($stmt_solicitacoes);
                
                // Definir cor do perfil
                $cor_perfil = '#95a5a6'; // Cor padrão
                switch($row['perfil_nome']) {
                    case 'Administrador':
                        $cor_perfil = '#ff6b6b';
                        break;
                    case 'Gestor':
                        $cor_perfil = '#4ecdc4';
                        break;
                    case 'Visualizador':
                        $cor_perfil = '#45b7d1';
                        break;
                }
                ?>
                <div class="user-card" <?php echo ($solicitacoes_pendentes > 0) ? 'style="border: 2px solid #f39c12; box-shadow: 0 0 10px rgba(243, 156, 18, 0.5);"' : ''; ?>>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <strong><?php echo htmlspecialchars($row['nome']); ?></strong><br>
                            <small>ID: <?php echo $row['id']; ?></small><br>
                            <small><?php echo htmlspecialchars($row['email']); ?></small><br>
                            <a href="usuario_itens.php?id=<?php echo $row['id']; ?>" style="color: #3498db; text-decoration: none; font-size: 0.9em;">
                                <i class="fas fa-box"></i> Itens do usuário
                            </a>
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <span class="badge" style="background-color: <?php echo $cor_perfil; ?>;"><?php echo htmlspecialchars($row['perfil_nome']); ?></span>
                        <span class="badge" style="background-color: #28a745; color: white;">Aprovado</span>
                        <?php if($solicitacoes_pendentes > 0): ?>
                            <br><span class="badge badge-warning" style="margin-top: 5px; display: inline-block;"><?php echo $solicitacoes_pendentes; ?> solicitação(ões)</span>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 10px;">
                        <a href="usuario_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-editar btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php 
                        // Verificar se o usuário tem movimentações
                        $sql_check_mov = "SELECT COUNT(*) as total FROM movimentacoes WHERE usuario_id = ?";
                        $stmt_check_mov = mysqli_prepare($link, $sql_check_mov);
                        mysqli_stmt_bind_param($stmt_check_mov, "i", $row['id']);
                        mysqli_stmt_execute($stmt_check_mov);
                        $result_check_mov = mysqli_stmt_get_result($stmt_check_mov);
                        $movimentacoes = mysqli_fetch_assoc($result_check_mov);
                        $tem_movimentacoes = $movimentacoes['total'] > 0;
                        mysqli_stmt_close($stmt_check_mov);
                        
                        // Mostrar o botão de exclusão apenas se o usuário não tiver movimentações
                        if (!$tem_movimentacoes): ?>
                            <a href="usuario_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-excluir btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <a href="usuarios.php?acao=rejeitar&id=<?php echo $row['id']; ?>" class="btn btn-rejeitar btn-sm" title="Rejeitar" onclick="return confirm('Tem certeza que deseja rejeitar este usuário? Ele será movido para a seção de usuários rejeitados.');">
                                <i class="fas fa-user-times"></i>
                            </a>
                        <?php endif; ?>
                        <a href="usuarios.php?acao=pendente&id=<?php echo $row['id']; ?>" class="btn btn-pendente btn-sm" title="Marcar como Pendente">
                            <i class="fas fa-clock"></i>
                        </a>
                        
                        <!-- Botão para gerar senha temporária -->
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="usuario_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="gerar_senha_temporaria" class="btn btn-warning btn-sm" title="Gerar Senha Temporária"
                                    <?php echo ($solicitacoes_pendentes > 0) ? '' : 'disabled'; ?>
                                    onclick="return confirm('Tem certeza que deseja gerar uma senha temporária para este usuário?');">
                                <i class="fas fa-key"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Nenhum usuário aprovado encontrado.</p>
        <?php endif; ?>
    </div>
</div>

<!-- REJEITADOS -->

<?php if ($count_pendentes == 0): ?>
    <!-- PENDENTES DE ACEITAÇÃO (na posição original quando não há pendências) -->
    <div class="user-section">
        <h3>PENDENTES DE ACEITAÇÃO</h3>
        <div class="user-list">
            <?php if ($result_pendentes && mysqli_num_rows($result_pendentes) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_pendentes)): ?>
                    <?php
                    // Definir cor do perfil
                    $cor_perfil = '#95a5a6'; // Cor padrão
                    switch($row['perfil_nome']) {
                        case 'Administrador':
                            $cor_perfil = '#ff6b6b';
                            break;
                        case 'Gestor':
                            $cor_perfil = '#4ecdc4';
                            break;
                        case 'Visualizador':
                            $cor_perfil = '#45b7d1';
                            break;
                    }
                    ?>
                    <div class="user-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong><?php echo htmlspecialchars($row['nome']); ?></strong><br>
                                <small>ID: <?php echo $row['id']; ?></small><br>
                                <small><?php echo htmlspecialchars($row['email']); ?></small><br>
                                <a href="usuario_itens.php?id=<?php echo $row['id']; ?>" style="color: #3498db; text-decoration: none; font-size: 0.9em;">
                                    <i class="fas fa-box"></i> Itens do usuário
                                </a>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <span class="badge" style="background-color: <?php echo $cor_perfil; ?>;"><?php echo htmlspecialchars($row['perfil_nome']); ?></span>
                            <span class="badge" style="background-color: #ffc107; color: white;">Pendente</span>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="usuarios.php?acao=aprovar&id=<?php echo $row['id']; ?>" class="btn btn-aprovar btn-sm" title="Aprovar">
                                <i class="fas fa-check"></i> Aprovar
                            </a>
                            <a href="usuarios.php?acao=rejeitar&id=<?php echo $row['id']; ?>" class="btn btn-rejeitar btn-sm" title="Rejeitar">
                                <i class="fas fa-times"></i> Rejeitar
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Nenhum usuário pendente encontrado.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<div class="user-section">
    <h3>REJEITADOS</h3>
    <div class="user-list">
        <?php if ($result_rejeitados && mysqli_num_rows($result_rejeitados) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result_rejeitados)): ?>
                <?php
                // Definir cor do perfil
                $cor_perfil = '#95a5a6'; // Cor padrão
                switch($row['perfil_nome']) {
                    case 'Administrador':
                        $cor_perfil = '#ff6b6b';
                        break;
                    case 'Gestor':
                        $cor_perfil = '#4ecdc4';
                        break;
                    case 'Visualizador':
                        $cor_perfil = '#45b7d1';
                        break;
                }
                ?>
                <div class="user-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <strong><?php echo htmlspecialchars($row['nome']); ?></strong><br>
                            <small>ID: <?php echo $row['id']; ?></small><br>
                            <small><?php echo htmlspecialchars($row['email']); ?></small><br>
                            <a href="usuario_itens.php?id=<?php echo $row['id']; ?>" style="color: #3498db; text-decoration: none; font-size: 0.9em;">
                                <i class="fas fa-box"></i> Itens do usuário
                            </a>
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <span class="badge" style="background-color: <?php echo $cor_perfil; ?>;"><?php echo htmlspecialchars($row['perfil_nome']); ?></span>
                        <span class="badge" style="background-color: #dc3545; color: white;">Rejeitado</span>
                    </div>
                    <div style="margin-top: 10px;">
                        <a href="usuarios.php?acao=aprovar&id=<?php echo $row['id']; ?>" class="btn btn-aprovar btn-sm" title="Aprovar">
                            <i class="fas fa-check"></i> Aprovar
                        </a>
                        <a href="usuarios.php?acao=pendente&id=<?php echo $row['id']; ?>" class="btn btn-pendente btn-sm" title="Marcar como Pendente">
                            <i class="fas fa-clock"></i> Pendente
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Nenhum usuário rejeitado encontrado.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Função para formatar o status do usuário
function formatarStatus(status) {
    const statusMap = {
        'pendente': 'Pendente',
        'aprovado': 'Aprovado',
        'rejeitado': 'Rejeitado'
    };
    return statusMap[status] || status;
}

// Função para obter a cor do perfil
function getCorPerfil(perfil) {
    switch(perfil) {
        case 'Administrador':
            return '#ff6b6b';
        case 'Gestor':
            return '#4ecdc4';
        case 'Visualizador':
            return '#45b7d1';
        default:
            return '#95a5a6';
    }
}

// Função para criar um card de usuário
function criarCardUsuario(usuario) {
    const card = document.createElement('div');
    card.className = 'user-card';
    
    // Verifica se há solicitações pendentes
    const temSolicitacoes = parseInt(usuario.solicitacoes_pendentes) > 0;
    
    // Adiciona classe de destaque se houver solicitações pendentes
    if (temSolicitacoes) {
        card.style.border = '2px solid #f39c12';
        card.style.boxShadow = '0 0 10px rgba(243, 156, 18, 0.5)';
    }
    
    // Define a cor do perfil
    const corPerfil = getCorPerfil(usuario.perfil_nome);
    
    card.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <strong>${usuario.nome}</strong><br>
                <small>ID: ${usuario.id}</small><br>
                <small>${usuario.email}</small><br>
                <a href="usuario_itens.php?id=${usuario.id}" style="color: #3498db; text-decoration: none; font-size: 0.9em;">
                    <i class="fas fa-box"></i> Itens do usuário
                </a>
            </div>
        </div>
        <div style="margin-top: 10px;">
            <span class="badge" style="background-color: ${corPerfil};">${usuario.perfil_nome}</span>
            <span class="badge" style="background-color: ${usuario.status === 'aprovado' ? '#28a745' : usuario.status === 'pendente' ? '#ffc107' : '#dc3545'}; color: white;">
                ${formatarStatus(usuario.status)}
            </span>
            ${temSolicitacoes ? `<br><span class="badge badge-warning" style="margin-top: 5px; display: inline-block;">${usuario.solicitacoes_pendentes} solicitação(ões)</span>` : ''}
        </div>
        <div style="margin-top: 10px;">
            ${usuario.status === 'pendente' ? 
                `<a href="usuarios.php?acao=aprovar&id=${usuario.id}" class="btn btn-aprovar btn-sm" title="Aprovar">
                    <i class="fas fa-check"></i>
                </a>
                <a href="usuarios.php?acao=rejeitar&id=${usuario.id}" class="btn btn-rejeitar btn-sm" title="Rejeitar">
                    <i class="fas fa-times"></i>
                </a>` :
                `<a href="usuario_edit.php?id=${usuario.id}" class="btn btn-editar btn-sm" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="usuario_delete.php?id=${usuario.id}" class="btn btn-excluir btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                    <i class="fas fa-trash"></i>
                </a>
                <a href="usuarios.php?acao=pendente&id=${usuario.id}" class="btn btn-pendente btn-sm" title="Marcar como Pendente">
                    <i class="fas fa-clock"></i>
                </a>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="usuario_id" value="${usuario.id}">
                    <button type="submit" name="gerar_senha_temporaria" class="btn btn-warning btn-sm" title="Gerar Senha Temporária"
                            ${temSolicitacoes ? '' : 'disabled'}
                            onclick="return confirm('Tem certeza que deseja gerar uma senha temporária para este usuário?');">
                        <i class="fas fa-key"></i>
                    </button>
                </form>`
            }
        </div>
    `;
    
    return card;
}

// Função para buscar usuários via AJAX
function buscarUsuarios(termo) {
    if (termo.length < 3) {
        document.getElementById('resultados-pesquisa').style.display = 'none';
        return;
    }
    
    fetch(`/inventario/api/buscar_usuarios.php?q=${encodeURIComponent(termo)}`)
        .then(response => response.json())
        .then(usuarios => {
            const container = document.getElementById('lista-resultados');
            container.innerHTML = '';
            
            if (usuarios.length > 0) {
                usuarios.forEach(usuario => {
                    container.appendChild(criarCardUsuario(usuario));
                });
                document.getElementById('resultados-pesquisa').style.display = 'block';
            } else {
                container.innerHTML = '<p>Nenhum usuário encontrado.</p>';
                document.getElementById('resultados-pesquisa').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erro ao buscar usuários:', error);
            document.getElementById('lista-resultados').innerHTML = '<p>Erro ao buscar usuários.</p>';
            document.getElementById('resultados-pesquisa').style.display = 'block';
        });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const campoPesquisa = document.getElementById('pesquisa-usuario');
    const botaoLimpar = document.getElementById('limpar-pesquisa');
    const resultadosPesquisa = document.getElementById('resultados-pesquisa');
    
    // Evento de digitação no campo de pesquisa
    let timeout;
    campoPesquisa.addEventListener('input', function() {
        clearTimeout(timeout);
        const termo = this.value.trim();
        
        if (termo.length >= 3) {
            timeout = setTimeout(() => {
                buscarUsuarios(termo);
            }, 300); // Aguarda 300ms após parar de digitar
        } else {
            resultadosPesquisa.style.display = 'none';
        }
    });
    
    // Botão para limpar pesquisa
    botaoLimpar.addEventListener('click', function() {
        campoPesquisa.value = '';
        resultadosPesquisa.style.display = 'none';
        campoPesquisa.focus();
    });
    
    // Funcionalidade de ordenação da tabela original
    const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;

    const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
        v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

    document.querySelectorAll('th[data-column]').forEach(th => {
        th.addEventListener('click', (() => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const column = Array.from(th.parentNode.children).indexOf(th);
            const currentIsAsc = th.classList.contains('asc');

            // Remove sorting classes from all headers
            document.querySelectorAll('th[data-column]').forEach(header => {
                header.classList.remove('asc', 'desc');
                header.querySelector('.sort-arrow').innerText = '';
            });

            // Add sorting class to the clicked header
            if (currentIsAsc) {
                th.classList.add('desc');
                th.querySelector('.sort-arrow').innerText = ' ↓'; // Down arrow
            } else {
                th.classList.add('asc');
                th.querySelector('.sort-arrow').innerText = ' ↑'; // Up arrow
            }

            Array.from(tbody.querySelectorAll('tr'))
                .sort(comparer(column, !currentIsAsc))
                .forEach(tr => tbody.appendChild(tr));
        }));
    });
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>
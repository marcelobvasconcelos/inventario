<?php
// item_add.php - Corrigido: fluxo POST seguro, validações e remoção de duplicidades

require_once 'config/db.php'; // Inicia sessão e expõe $link/$pdo

// Inicialização de variáveis (valores do formulário e erros)
$nome = $patrimonio_novo = $patrimonio_secundario = $local_id = $estado = $observacao = '';
$responsavel_id = '';
$nome_err = $patrimonio_novo_err = $local_id_err = $responsavel_id_err = $estado_err = '';

// Variáveis de controle para gestores
$is_gestor_sem_local = false;
$locais_result = false;
$usuarios_result = false;

// Verifica perfil para carregar listas e regras (Gestor/Admin)
$usuario_logado_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
$perfil = isset($_SESSION['permissao']) ? $_SESSION['permissao'] : '';

if ($perfil === 'Gestor') {
    // Verifica se o gestor já tem itens
    $check_itens_sql = "SELECT COUNT(*) FROM itens WHERE responsavel_id = ?";
    if ($stmt_check_itens = mysqli_prepare($link, $check_itens_sql)) {
        mysqli_stmt_bind_param($stmt_check_itens, 'i', $usuario_logado_id);
        mysqli_stmt_execute($stmt_check_itens);
        mysqli_stmt_bind_result($stmt_check_itens, $count_itens);
        mysqli_stmt_fetch($stmt_check_itens);
        mysqli_stmt_close($stmt_check_itens);
        $is_gestor_sem_local = ($count_itens == 0);
    }
    // Locais disponíveis conforme a regra
    if ($is_gestor_sem_local) {
        $locais_sql = "SELECT id, nome FROM locais ORDER BY nome ASC";
        $locais_result = mysqli_query($link, $locais_sql);
    } else {
        $locais_sql = "SELECT DISTINCT l.id, l.nome FROM locais l JOIN itens i ON l.id = i.local_id WHERE i.responsavel_id = ? ORDER BY l.nome ASC";
        if ($stmt_locais = mysqli_prepare($link, $locais_sql)) {
            mysqli_stmt_bind_param($stmt_locais, 'i', $usuario_logado_id);
            mysqli_stmt_execute($stmt_locais);
            $locais_result = mysqli_stmt_get_result($stmt_locais);
            mysqli_stmt_close($stmt_locais);
        }
    }
    // Responsável pré-definido para gestor
    $responsavel_id = $usuario_logado_id;
} elseif ($perfil === 'Administrador') {
    // Admin: todos os locais e usuários aprovados
    $locais_result = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
    $usuarios_result = mysqli_query($link, "SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC");
} else {
    // Outros perfis não devem acessar esta página (garantido novamente depois do header)
}

// Permite pré-selecionar local via GET quando gestor sem local seleciona primeiro
if (isset($_GET['local_id']) && $perfil === 'Gestor' && $is_gestor_sem_local) {
    $local_id = $_GET['local_id'];
}

// Processamento do formulário antes de qualquer saída (evita headers already sent)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Garantir que está logado
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
    // Apenas Gestor/Admin
    if ($perfil !== 'Administrador' && $perfil !== 'Gestor') {
        header('Location: itens.php');
        exit;
    }

    // Fluxo de seleção de local inicial para gestor sem local
    if ($perfil === 'Gestor' && $is_gestor_sem_local && isset($_POST['selecionar_local_primeiro'])) {
        $local_id = isset($_POST['local_id']) ? trim($_POST['local_id']) : '';
        if (empty($local_id)) {
            $local_id_err = 'Por favor, selecione um local.';
        } else {
            header('Location: item_add.php?local_id=' . urlencode($local_id));
            exit;
        }
    } else {
        // Validações básicas
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        if ($nome === '') {
            $nome_err = 'Por favor, insira o nome do item.';
        }

        $patrimonio_novo = isset($_POST['patrimonio_novo']) ? trim($_POST['patrimonio_novo']) : '';
        if ($patrimonio_novo === '') {
            $patrimonio_novo_err = 'Por favor, insira o patrimônio.';
        }

        $patrimonio_secundario = isset($_POST['patrimonio_secundario']) ? trim($_POST['patrimonio_secundario']) : '';
        $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
        if ($estado === '') {
            $estado_err = 'Por favor, selecione o estado.';
        }
        $observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';

        // Local
        $local_id = isset($_POST['local_id']) ? trim($_POST['local_id']) : '';
        if ($local_id === '') {
            $local_id_err = 'Por favor, selecione um local.';
        } else if ($perfil === 'Gestor' && !$is_gestor_sem_local) {
            // Verificação de permissão do gestor para o local (apenas se ele já tem itens)
            $check_local_sql = "SELECT COUNT(*) FROM itens WHERE responsavel_id = ? AND local_id = ?";
            if ($stmt_check_local = mysqli_prepare($link, $check_local_sql)) {
                mysqli_stmt_bind_param($stmt_check_local, 'ii', $usuario_logado_id, $local_id);
                mysqli_stmt_execute($stmt_check_local);
                mysqli_stmt_bind_result($stmt_check_local, $count_local);
                mysqli_stmt_fetch($stmt_check_local);
                mysqli_stmt_close($stmt_check_local);
                if ($count_local == 0) {
                    $local_id_err = 'Você não tem permissão para adicionar itens neste local.';
                }
            }
        }

        // Responsável
        if ($perfil === 'Gestor') {
            $responsavel_id = $usuario_logado_id; // já definido
        } else { // Administrador
            $responsavel_id = isset($_POST['responsavel_id']) ? trim($_POST['responsavel_id']) : '';
            if ($responsavel_id === '') {
                $responsavel_id_err = 'Por favor, selecione um responsável.';
            }
        }

        // Inserção se não houver erros
        if ($nome_err === '' && $patrimonio_novo_err === '' && $local_id_err === '' && $responsavel_id_err === '' && $estado_err === '') {
            $status_confirmacao = ((int)$responsavel_id === $usuario_logado_id) ? 'Confirmado' : 'Pendente';
            $sql_insert = "INSERT INTO itens (nome, patrimonio_novo, patrimonio_secundario, local_id, responsavel_id, estado, observacao, data_cadastro, status_confirmacao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            if ($stmt = mysqli_prepare($link, $sql_insert)) {
                mysqli_stmt_bind_param($stmt, 'sssiisss', $nome, $patrimonio_novo, $patrimonio_secundario, $local_id, $responsavel_id, $estado, $observacao, $status_confirmacao);
                if (mysqli_stmt_execute($stmt)) {
                    $novo_item_id = mysqli_insert_id($link);

                    // Notificação de novo item via notificacoes_movimentacao (pendente para confirmação)
                    if ($status_confirmacao === 'Pendente') {
                        $ok = true;
                        mysqli_begin_transaction($link);

                        // 1) Criar uma movimentação inicial (cadastro) com origem = destino = local atual
                        $sql_mov = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id, usuario_destino_id) VALUES (?, ?, ?, ?, NULL, ?)";
                        $stmt_mov = mysqli_prepare($link, $sql_mov);
                        if ($stmt_mov) {
                            mysqli_stmt_bind_param($stmt_mov, "iiiii", $novo_item_id, $local_id, $local_id, $usuario_logado_id, $responsavel_id);
                            if (!mysqli_stmt_execute($stmt_mov)) { $ok = false; }
                            $movimentacao_id = mysqli_insert_id($link);
                            mysqli_stmt_close($stmt_mov);
                        } else { $ok = false; }

                        // 2) Criar a notificação pendente atrelada à movimentação
                        if ($ok) {
                            $sql_nm = "INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')";
                            $stmt_nm = mysqli_prepare($link, $sql_nm);
                            if ($stmt_nm) {
                                mysqli_stmt_bind_param($stmt_nm, "iii", $movimentacao_id, $novo_item_id, $responsavel_id);
                                if (!mysqli_stmt_execute($stmt_nm)) { $ok = false; }
                                mysqli_stmt_close($stmt_nm);
                            } else { $ok = false; }
                        }

                        if ($ok) {
                            mysqli_commit($link);
                        } else {
                            mysqli_rollback($link);
                        }
                    }

                    header('Location: itens.php');
                    exit;
                } else {
                    // Trata erro de duplicidade para patrimonio_novo
                    if (mysqli_errno($link) == 1062 && strpos(mysqli_error($link), 'patrimonio_novo') !== false) {
                        if (preg_match("/Duplicate entry '([^']+)'/", mysqli_error($link), $matches)) {
                            $dup = isset($matches[1]) ? $matches[1] : $patrimonio_novo;
                            $patrimonio_novo_err = 'Este patrimônio ' . htmlspecialchars($dup) . ' já está cadastrado!';
                        } else {
                            $patrimonio_novo_err = 'Patrimônio já cadastrado!';
                        }
                    } else {
                        $nome_err = 'Oops! Algo deu errado. Por favor, tente novamente mais tarde.';
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                $nome_err = 'Erro ao preparar a consulta de inserção: ' . mysqli_error($link);
            }
        }
    }
}

// A partir daqui pode renderizar a página
require_once 'includes/header.php';

// Verifica permissão após renderizar cabeçalho (mantém padrão do projeto)
if ($perfil !== 'Administrador' && $perfil !== 'Gestor') {
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<h2>Adicionar Novo Item</h2>

<?php if ($perfil === 'Gestor' && $is_gestor_sem_local && empty($local_id)): ?>
    <div class="alert alert-info">
        Você ainda não possui nenhum item cadastrado. Para começar, selecione um local para associar ao seu primeiro item.
    </div>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <div>
            <label>Selecione um Local</label>
            <select name="local_id" required>
                <option value="">Selecione um Local</option>
                <?php if ($locais_result): ?>
                    <?php while($local = mysqli_fetch_assoc($locais_result)): ?>
                        <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <span class="help-block"><?php echo $local_id_err; ?></span>
        </div>
        <div>
            <input type="hidden" name="selecionar_local_primeiro" value="1">
            <input type="submit" value="Continuar">
        </div>
    </form>
    <div class="alert alert-info" style="margin-top: 20px;">
        Não encontrou seu local? <a href="local_request.php" class="btn-custom">Solicitar Novo Local</a>
    </div>
<?php else: ?>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <div>
            <label>Nome</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
            <span class="help-block"><?php echo $nome_err; ?></span>
        </div>
        <div>
            <label>Patrimônio Novo</label>
            <input type="text" name="patrimonio_novo" value="<?php echo htmlspecialchars($patrimonio_novo); ?>" required>
            <span class="help-block"><?php echo $patrimonio_novo_err; ?></span>
        </div>
        <div>
            <label>Patrimônio Secundário (Opcional)</label>
            <input type="text" name="patrimonio_secundario" value="<?php echo htmlspecialchars($patrimonio_secundario); ?>">
        </div>
        <div>
            <label>Local</label>
            <select name="local_id" required <?php echo ($perfil === 'Gestor' && $is_gestor_sem_local && !empty($local_id)) ? 'disabled' : ''; ?>>
                <option value="">Selecione um Local</option>
                <?php if ($locais_result): ?>
                    <?php mysqli_data_seek($locais_result, 0); ?>
                    <?php while($local = mysqli_fetch_assoc($locais_result)): ?>
                        <option value="<?php echo $local['id']; ?>" <?php echo ($local['id'] == $local_id) ? 'selected' : ''; ?>><?php echo $local['nome']; ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <?php if ($perfil === 'Gestor' && $is_gestor_sem_local && !empty($local_id)): ?>
                <input type="hidden" name="local_id" value="<?php echo htmlspecialchars($local_id); ?>">
            <?php endif; ?>
            <span class="help-block"><?php echo $local_id_err; ?></span>
        </div>
        <div>
            <label>Responsável</label>
            <?php if ($perfil === 'Gestor'): ?>
                <input type="text" value="<?php echo htmlspecialchars($_SESSION['nome']); ?>" disabled>
                <input type="hidden" name="responsavel_id" value="<?php echo (int)$_SESSION['id']; ?>">
            <?php else: ?>
                <select name="responsavel_id" required>
                    <option value="">Selecione um Responsável</option>
                    <?php if ($usuarios_result): ?>
                        <?php while($usuario = mysqli_fetch_assoc($usuarios_result)): ?>
                            <option value="<?php echo $usuario['id']; ?>" <?php echo ($usuario['id'] == $responsavel_id) ? 'selected' : ''; ?>><?php echo $usuario['nome']; ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <span class="help-block"><?php echo $responsavel_id_err; ?></span>
            <?php endif; ?>
        </div>
        <div>
            <label>Estado</label>
            <select name="estado" required>
                <option value="">Selecione o Estado</option>
                <option value="Em uso" <?php echo ($estado === 'Em uso') ? 'selected' : ''; ?>>Em uso</option>
                <option value="Ocioso" <?php echo ($estado === 'Ocioso') ? 'selected' : ''; ?>>Ocioso</option>
                <option value="Recuperável" <?php echo ($estado === 'Recuperável') ? 'selected' : ''; ?>>Recuperável</option>
                <option value="Inservível" <?php echo ($estado === 'Inservível') ? 'selected' : ''; ?>>Inservível</option>
            </select>
            <span class="help-block"><?php echo $estado_err; ?></span>
        </div>
        <div>
            <label>Observação (Opcional)</label>
            <textarea name="observacao"><?php echo htmlspecialchars($observacao); ?></textarea>
        </div>
        <div>
            <input type="submit" value="Adicionar">
            <a href="itens.php" class="btn-custom">Cancelar</a>
        </div>
    </form>
<?php endif; ?>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>

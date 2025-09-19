<?php
// Componente de menu reutilizável para o módulo de almoxarifado.
// A página que incluir este arquivo deve definir as variáveis de permissão.

// Detectar página atual
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Função para verificar se o menu está ativo
function isActive($page, $current_page, $additional_pages = []) {
    if ($page === $current_page) return true;
    return in_array($current_page, $additional_pages);
}
?>
<a href="index.php" class="btn-custom <?php echo isActive('index.php', $current_page) ? 'active' : ''; ?>"><i class="fas fa-home"></i> Início</a>
<a href="dashboard.php" class="btn-custom <?php echo isActive('dashboard.php', $current_page) ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
<a href="estatisticas.php" class="btn-custom <?php echo isActive('estatisticas.php', $current_page) ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Estatísticas</a>
<a href="requisicao.php" class="btn-custom <?php echo isActive('requisicao.php', $current_page, ['requisicao_massa.php']) ? 'active' : ''; ?>"><i class="fas fa-plus"></i> Nova Requisição</a>
<a href="notificacoes.php" class="btn-custom <?php echo isActive('notificacoes.php', $current_page, ['minhas_notificacoes.php']) ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Minhas Notificações</a>
<?php if (isset($_SESSION["permissao"]) && $_SESSION["permissao"] == 'Administrador'): ?>
    <a href="admin_notificacoes.php" class="btn-custom <?php echo isActive('admin_notificacoes.php', $current_page) ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Gerenciar Requisições</a>
    <a href="empenhos_index.php" class="btn-custom <?php echo isActive('empenhos_index.php', $current_page, ['empenho_add.php', 'empenho_edit.php', 'nota_fiscal_add.php', 'nota_fiscal_edit.php', 'nota_fiscal_detalhes.php', 'material_add.php', 'material_edit.php', 'categoria_add.php']) ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Gestão Financeira</a>
    <a href="entrada_material.php" class="btn-custom <?php echo isActive('entrada_material.php', $current_page, ['entrada_add.php', 'entradas.php']) ? 'active' : ''; ?>"><i class="fas fa-arrow-down"></i> Registrar Entrada de Material</a>
    <a href="historico_saldo_empenhos.php" class="btn-custom <?php echo isActive('historico_saldo_empenhos.php', $current_page) ? 'active' : ''; ?>"><i class="fas fa-history"></i> Histórico de Empenhos</a>
<?php endif; ?>
<?php
// limpar_feedbacks_teste.php - Script para limpar feedbacks de teste
require_once 'includes/header.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Verificar se foi confirmada a limpeza
if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sim') {
    // Limpar o arquivo CSV mantendo apenas o cabeçalho
    $arquivo = 'feedback_testes_sistema.csv';
    $cabecalho = 'data_hora,perfil_usuario,nome_usuario,email_usuario,navegacao_intuitiva,perfis_claros,acesso_negado,autenticacao_comentarios,cadastro_itens,movimentacoes_itens,gestao_locais,relatorios_legado,legado_usabilidade,legado_calculos,legado_comentarios,cadastro_materiais,gestao_empenhos,entrada_materiais,requisicoes_materiais,controle_estoque,relatorios_almoxarifado,almoxarifado_vinculacao,almoxarifado_historico,almoxarifado_auditoria,almoxarifado_calculos_teste1,almoxarifado_calculos_teste2,almoxarifado_calculos_teste3,almoxarifado_comentarios,integracao_modulos,integracao_vinculacao,integracao_historico,integracao_auditoria,integracao_comentarios,interface_intuitiva,menus_posicionamento,formularios_facilidade,validacoes_claras,busca_filtros,ergonomia_comentarios,controle_acesso,validacao_dados,seguranca_comentarios,tempo_resposta,consistencia_dados,performance_comentarios,funcionalidades_adicionar,funcionalidades_aprimorar,facilidade_uso,eficiencia_sistema,aparencia_sistema,comentarios_finais,problemas_encontrados,sugestoes_especificas' . "\n";
    
    if (file_put_contents($arquivo, $cabecalho)) {
        echo "<div class='alert alert-success'>Feedbacks de teste limpos com sucesso!</div>";
    } else {
        echo "<div class='alert alert-danger'>Erro ao limpar feedbacks de teste.</div>";
    }
} else {
    ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>🧹 Limpar Feedbacks de Teste</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h4><i class="fas fa-exclamation-triangle"></i> Atenção!</h4>
                    <p>Esta ação irá remover todos os feedbacks de teste coletados até o momento.</p>
                    <p><strong>Esta operação não pode ser desfeita.</strong></p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="confirmar" value="sim">
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja limpar todos os feedbacks de teste? Esta ação não pode ser desfeita.')">
                            <i class="fas fa-trash-alt"></i> Limpar Todos os Feedbacks
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php
}

require_once 'includes/footer.php';
?>
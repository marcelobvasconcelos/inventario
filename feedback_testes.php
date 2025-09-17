<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Feedback - Testes do Sistema</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f6f9;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #124a80;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #124a80;
            padding-bottom: 15px;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-top: 0;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .question {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .question label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input[type="text"], 
        input[type="email"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .radio-group, .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .radio-option, .checkbox-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .rating-scale {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .rating-scale span {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .submit-btn {
            background: #27ae60;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            display: block;
            margin: 30px auto;
            width: 200px;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #219653;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        
        .required {
            color: #e74c3c;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .radio-group, .checkbox-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Formulário de Feedback - Testes do Sistema de Inventário e Almoxarifado</h1>
        
        <p style="text-align: center; margin-bottom: 30px;">
            Por favor, preencha este formulário com base em sua experiência ao testar o sistema.<br>
            Seu feedback é fundamental para melhorarmos a qualidade e usabilidade do sistema.
        </p>
        
        <?php
        // Processar formulário quando enviado
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Coletar dados do formulário
            $dados = [
                'data_hora' => date('Y-m-d H:i:s'),
                'perfil_usuario' => $_POST['perfil_usuario'] ?? '',
                'nome_usuario' => $_POST['nome_usuario'] ?? '',
                'email_usuario' => $_POST['email_usuario'] ?? '',
                
                // Seção 1: Autenticação e Perfis
                'navegacao_intuitiva' => $_POST['navegacao_intuitiva'] ?? '',
                'perfis_claros' => $_POST['perfis_claros'] ?? '',
                'acesso_negado' => $_POST['acesso_negado'] ?? '',
                'autenticacao_comentarios' => $_POST['autenticacao_comentarios'] ?? '',
                
                // Seção 2: Módulo Legado
                'cadastro_itens' => $_POST['cadastro_itens'] ?? '',
                'movimentacoes_itens' => $_POST['movimentacoes_itens'] ?? '',
                'gestao_locais' => $_POST['gestao_locais'] ?? '',
                'relatorios_legado' => $_POST['relatorios_legado'] ?? '',
                'legado_usabilidade' => $_POST['legado_usabilidade'] ?? '',
                'legado_calculos' => $_POST['legado_calculos'] ?? '',
                'legado_comentarios' => $_POST['legado_comentarios'] ?? '',
                
                // Seção 3: Módulo Almoxarifado
                'cadastro_materiais' => $_POST['cadastro_materiais'] ?? '',
                'gestao_empenhos' => $_POST['gestao_empenhos'] ?? '',
                'entrada_materiais' => $_POST['entrada_materiais'] ?? '',
                'requisicoes_materiais' => $_POST['requisicoes_materiais'] ?? '',
                'controle_estoque' => $_POST['controle_estoque'] ?? '',
                'relatorios_almoxarifado' => $_POST['relatorios_almoxarifado'] ?? '',
                'almoxarifado_vinculacao' => $_POST['almoxarifado_vinculacao'] ?? '',
                'almoxarifado_historico' => $_POST['almoxarifado_historico'] ?? '',
                'almoxarifado_auditoria' => $_POST['almoxarifado_auditoria'] ?? '',
                'almoxarifado_calculos_teste1' => $_POST['almoxarifado_calculos_teste1'] ?? '',
                'almoxarifado_calculos_teste2' => $_POST['almoxarifado_calculos_teste2'] ?? '',
                'almoxarifado_calculos_teste3' => $_POST['almoxarifado_calculos_teste3'] ?? '',
                'almoxarifado_comentarios' => $_POST['almoxarifado_comentarios'] ?? '',
                
                // Seção 4: Integração
                'integracao_modulos' => $_POST['integracao_modulos'] ?? '',
                'integracao_vinculacao' => $_POST['integracao_vinculacao'] ?? '',
                'integracao_historico' => $_POST['integracao_historico'] ?? '',
                'integracao_auditoria' => $_POST['integracao_auditoria'] ?? '',
                'integracao_comentarios' => $_POST['integracao_comentarios'] ?? '',
                
                // Seção 5: Ergonomia
                'interface_intuitiva' => $_POST['interface_intuitiva'] ?? '',
                'menus_posicionamento' => $_POST['menus_posicionamento'] ?? '',
                'formularios_facilidade' => $_POST['formularios_facilidade'] ?? '',
                'validacoes_claras' => $_POST['validacoes_claras'] ?? '',
                'busca_filtros' => $_POST['busca_filtros'] ?? '',
                'ergonomia_comentarios' => $_POST['ergonomia_comentarios'] ?? '',
                
                // Seção 6: Segurança
                'controle_acesso' => $_POST['controle_acesso'] ?? '',
                'validacao_dados' => $_POST['validacao_dados'] ?? '',
                'seguranca_comentarios' => $_POST['seguranca_comentarios'] ?? '',
                
                // Seção 7: Performance
                'tempo_resposta' => $_POST['tempo_resposta'] ?? '',
                'consistencia_dados' => $_POST['consistencia_dados'] ?? '',
                'performance_comentarios' => $_POST['performance_comentarios'] ?? '',
                
                // Seção 8: Feedback Geral
                'funcionalidades_adicionar' => $_POST['funcionalidades_adicionar'] ?? '',
                'funcionalidades_aprimorar' => $_POST['funcionalidades_aprimorar'] ?? '',
                'facilidade_uso' => $_POST['facilidade_uso'] ?? '',
                'eficiencia_sistema' => $_POST['eficiencia_sistema'] ?? '',
                'aparencia_sistema' => $_POST['aparencia_sistema'] ?? '',
                'comentarios_finais' => $_POST['comentarios_finais'] ?? '',
                'problemas_encontrados' => $_POST['problemas_encontrados'] ?? '',
                'sugestoes_especificas' => $_POST['sugestoes_especificas'] ?? ''
            ];
            
            // Salvar dados em arquivo CSV
            $arquivo = 'feedback_testes_sistema.csv';
            $cabecalhos = array_keys($dados);
            
            // Verificar se arquivo existe, se não, criar com cabeçalhos
            if (!file_exists($arquivo)) {
                $fp = fopen($arquivo, 'w');
                fputcsv($fp, $cabecalhos);
                fclose($fp);
            }
            
            // Adicionar dados ao arquivo
            $fp = fopen($arquivo, 'a');
            fputcsv($fp, $dados);
            fclose($fp);
            
            echo '<div class="success-message">';
            echo '<h3>✅ Feedback Recebido com Sucesso!</h3>';
            echo '<p>Obrigado por dedicar seu tempo para fornecer este feedback valioso.</p>';
            echo '<p>Seu input será fundamental para melhorarmos o sistema.</p>';
            echo '</div>';
            
            // Não mostrar o formulário novamente
            return;
        }
        ?>
        
        <form method="POST" action="">
            <!-- Seção de Identificação -->
            <div class="section">
                <h2>👤 Informações de Identificação</h2>
                
                <div class="question">
                    <label for="perfil_usuario">Qual seu perfil de usuário no sistema? <span class="required">*</span></label>
                    <select id="perfil_usuario" name="perfil_usuario" required>
                        <option value="">Selecione seu perfil</option>
                        <option value="administrador">Administrador</option>
                        <option value="almoxarife">Almoxarife</option>
                        <option value="gestor">Gestor</option>
                        <option value="visualizador">Visualizador</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                
                <div class="question">
                    <label for="nome_usuario">Seu nome (opcional):</label>
                    <input type="text" id="nome_usuario" name="nome_usuario" placeholder="Digite seu nome">
                </div>
                
                <div class="question">
                    <label for="email_usuario">Seu e-mail (opcional):</label>
                    <input type="email" id="email_usuario" name="email_usuario" placeholder="Digite seu e-mail">
                </div>
            </div>
            
            <!-- Seção 1: Autenticação e Perfis -->
            <div class="section">
                <h2>🔐 Autenticação e Perfis de Usuário</h2>
                
                <div class="question">
                    <label>A navegação entre perfis está clara e intuitiva?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="navegacao_sim" name="navegacao_intuitiva" value="sim" required>
                            <label for="navegacao_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="navegacao_nao" name="navegacao_intuitiva" value="não">
                            <label for="navegacao_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="navegacao_parcial" name="navegacao_intuitiva" value="parcialmente">
                            <label for="navegacao_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Você conseguiu identificar facilmente quais funcionalidades estão disponíveis para o seu perfil?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="perfis_sim" name="perfis_claros" value="sim" required>
                            <label for="perfis_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="perfis_nao" name="perfis_claros" value="não">
                            <label for="perfis_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="perfis_parcial" name="perfis_claros" value="parcialmente">
                            <label for="perfis_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Houve alguma situação em que você esperava ter acesso a uma funcionalidade, mas não teve?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="acesso_sim" name="acesso_negado" value="sim" required>
                            <label for="acesso_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="acesso_nao" name="acesso_negado" value="não">
                            <label for="acesso_nao">Não</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label for="autenticacao_comentarios">Comentários sobre autenticação e perfis de usuário:</label>
                    <textarea id="autenticacao_comentarios" name="autenticacao_comentarios" placeholder="Compartilhe sua experiência com a autenticação e perfis de usuário..."></textarea>
                </div>
            </div>
            
            <!-- Seção 2: Módulo Legado de Inventário -->
            <div class="section">
                <h2>📦 Módulo Legado de Inventário</h2>
                
                <div class="question">
                    <label>As telas de cadastro e edição são intuitivas e fáceis de usar?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="cadastro_sim" name="cadastro_itens" value="sim" required>
                            <label for="cadastro_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="cadastro_nao" name="cadastro_itens" value="não">
                            <label for="cadastro_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="cadastro_parcial" name="cadastro_itens" value="parcialmente">
                            <label for="cadastro_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Os cálculos de saldo estão corretos após movimentações?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="movimentacoes_sim" name="movimentacoes_itens" value="sim" required>
                            <label for="movimentacoes_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="movimentacoes_nao" name="movimentacoes_itens" value="não">
                            <label for="movimentacoes_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="movimentacoes_parcial" name="movimentacoes_itens" value="parcialmente">
                            <label for="movimentacoes_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>As notificações são claras e informativas?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="notificacoes_sim" name="gestao_locais" value="sim" required>
                            <label for="notificacoes_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="notificacoes_nao" name="gestao_locais" value="não">
                            <label for="notificacoes_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="notificacoes_parcial" name="gestao_locais" value="parcialmente">
                            <label for="notificacoes_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Há alguma funcionalidade que você sente falta ou que poderia ser aprimorada?</label>
                    <textarea id="legado_comentarios" name="legado_comentarios" placeholder="Compartilhe sua experiência com o módulo legado..."></textarea>
                </div>
            </div>
            
            <!-- Seção 3: Módulo de Almoxarifado -->
            <div class="section">
                <h2>🏢 Módulo de Almoxarifado</h2>
                
                <div class="question">
                    <label>O processo de cadastro de materiais é intuitivo?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="materiais_sim" name="cadastro_materiais" value="sim" required>
                            <label for="materiais_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="materiais_nao" name="cadastro_materiais" value="não">
                            <label for="materiais_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="materiais_parcial" name="cadastro_materiais" value="parcialmente">
                            <label for="materiais_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>A vinculação entre empenhos e notas fiscais está clara?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="empenhos_sim" name="gestao_empenhos" value="sim" required>
                            <label for="empenhos_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="empenhos_nao" name="gestao_empenhos" value="não">
                            <label for="empenhos_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="empenhos_parcial" name="gestao_empenhos" value="parcialmente">
                            <label for="empenhos_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>O processo de registro de entradas está claro?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="entradas_sim" name="entrada_materiais" value="sim" required>
                            <label for="entradas_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="entradas_nao" name="entrada_materiais" value="não">
                            <label for="entradas_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="entradas_parcial" name="entrada_materiais" value="parcialmente">
                            <label for="entradas_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Os saldos estão sendo atualizados corretamente após entradas?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="saldos_sim" name="almoxarifado_calculos_teste1" value="sim" required>
                            <label for="saldos_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="saldos_nao" name="almoxarifado_calculos_teste1" value="não">
                            <label for="saldos_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="saldos_parcial" name="almoxarifado_calculos_teste1" value="parcialmente">
                            <label for="saldos_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>O processo de requisição é intuitivo?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="requisicoes_sim" name="requisicoes_materiais" value="sim" required>
                            <label for="requisicoes_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="requisicoes_nao" name="requisicoes_materiais" value="não">
                            <label for="requisicoes_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="requisicoes_parcial" name="requisicoes_materiais" value="parcialmente">
                            <label for="requisicoes_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>As notificações de requisições são claras e oportunas?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="notificacoes_req_sim" name="almoxarifado_calculos_teste2" value="sim" required>
                            <label for="notificacoes_req_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="notificacoes_req_nao" name="almoxarifado_calculos_teste2" value="não">
                            <label for="notificacoes_req_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="notificacoes_req_parcial" name="almoxarifado_calculos_teste2" value="parcialmente">
                            <label for="notificacoes_req_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Os níveis de alerta de estoque estão adequados?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="estoque_sim" name="controle_estoque" value="sim" required>
                            <label for="estoque_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="estoque_nao" name="controle_estoque" value="não">
                            <label for="estoque_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="estoque_parcial" name="controle_estoque" value="parcialmente">
                            <label for="estoque_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Os relatórios fornecem as informações necessárias?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="relatorios_sim" name="relatorios_almoxarifado" value="sim" required>
                            <label for="relatorios_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="relatorios_nao" name="relatorios_almoxarifado" value="não">
                            <label for="relatorios_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="relatorios_parcial" name="relatorios_almoxarifado" value="parcialmente">
                            <label for="relatorios_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>A vinculação direta de materiais às notas fiscais facilita a rastreabilidade?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="vinculacao_sim" name="almoxarifado_vinculacao" value="sim" required>
                            <label for="vinculacao_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="vinculacao_nao" name="almoxarifado_vinculacao" value="não">
                            <label for="vinculacao_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="vinculacao_parcial" name="almoxarifado_vinculacao" value="parcialmente">
                            <label for="vinculacao_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>O histórico de alterações de saldo é útil e informativo?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="historico_sim" name="almoxarifado_historico" value="sim" required>
                            <label for="historico_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="historico_nao" name="almoxarifado_historico" value="não">
                            <label for="historico_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="historico_parcial" name="almoxarifado_historico" value="parcialmente">
                            <label for="historico_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>As informações de auditoria são úteis?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="auditoria_sim" name="almoxarifado_auditoria" value="sim" required>
                            <label for="auditoria_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="auditoria_nao" name="almoxarifado_auditoria" value="não">
                            <label for="auditoria_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="auditoria_parcial" name="almoxarifado_auditoria" value="parcialmente">
                            <label for="auditoria_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label for="almoxarifado_comentarios">Comentários sobre o módulo de almoxarifado:</label>
                    <textarea id="almoxarifado_comentarios" name="almoxarifado_comentarios" placeholder="Compartilhe sua experiência com o módulo de almoxarifado..."></textarea>
                </div>
            </div>
            
            <!-- Seção 4: Integração entre Módulos -->
            <div class="section">
                <h2>🔗 Integração entre Módulos</h2>
                
                <div class="question">
                    <label>A integração entre os módulos está funcionando corretamente?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="integracao_sim" name="integracao_modulos" value="sim" required>
                            <label for="integracao_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_nao" name="integracao_modulos" value="não">
                            <label for="integracao_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_parcial" name="integracao_modulos" value="parcialmente">
                            <label for="integracao_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>A vinculação direta de materiais às notas fiscais está funcionando corretamente?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="integracao_vinculacao_sim" name="integracao_vinculacao" value="sim" required>
                            <label for="integracao_vinculacao_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_vinculacao_nao" name="integracao_vinculacao" value="não">
                            <label for="integracao_vinculacao_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_vinculacao_parcial" name="integracao_vinculacao" value="parcialmente">
                            <label for="integracao_vinculacao_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>O histórico de alterações de saldo dos empenhos está completo e preciso?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="integracao_historico_sim" name="integracao_historico" value="sim" required>
                            <label for="integracao_historico_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_historico_nao" name="integracao_historico" value="não">
                            <label for="integracao_historico_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_historico_parcial" name="integracao_historico" value="parcialmente">
                            <label for="integracao_historico_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>As informações de auditoria estão sendo registradas corretamente?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="integracao_auditoria_sim" name="integracao_auditoria" value="sim" required>
                            <label for="integracao_auditoria_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_auditoria_nao" name="integracao_auditoria" value="não">
                            <label for="integracao_auditoria_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="integracao_auditoria_parcial" name="integracao_auditoria" value="parcialmente">
                            <label for="integracao_auditoria_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label for="integracao_comentarios">Comentários sobre a integração entre módulos:</label>
                    <textarea id="integracao_comentarios" name="integracao_comentarios" placeholder="Compartilhe sua experiência com a integração entre os módulos..."></textarea>
                </div>
            </div>
            
            <!-- Seção 5: Ergonomia e Usabilidade -->
            <div class="section">
                <h2>🎨 Ergonomia e Usabilidade</h2>
                
                <div class="question">
                    <label>A interface do sistema é intuitiva e fácil de usar?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="interface_sim" name="interface_intuitiva" value="sim" required>
                            <label for="interface_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="interface_nao" name="interface_intuitiva" value="não">
                            <label for="interface_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="interface_parcial" name="interface_intuitiva" value="parcialmente">
                            <label for="interface_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Os menus e botões estão posicionados de forma intuitiva?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="menus_sim" name="menus_posicionamento" value="sim" required>
                            <label for="menus_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="menus_nao" name="menus_posicionamento" value="não">
                            <label for="menus_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="menus_parcial" name="menus_posicionamento" value="parcialmente">
                            <label for="menus_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Os formulários são fáceis de preencher?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="formularios_sim" name="formularios_facilidade" value="sim" required>
                            <label for="formularios_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="formularios_nao" name="formularios_facilidade" value="não">
                            <label for="formularios_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="formularios_parcial" name="formularios_facilidade" value="parcialmente">
                            <label for="formularios_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>As mensagens de erro são claras e ajudam a corrigir os problemas?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="validacoes_sim" name="validacoes_claras" value="sim" required>
                            <label for="validacoes_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="validacoes_nao" name="validacoes_claras" value="não">
                            <label for="validacoes_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="validacoes_parcial" name="validacoes_claras" value="parcialmente">
                            <label for="validacoes_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>As funções de busca e filtro são eficazes e fáceis de usar?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="busca_sim" name="busca_filtros" value="sim" required>
                            <label for="busca_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="busca_nao" name="busca_filtros" value="não">
                            <label for="busca_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="busca_parcial" name="busca_filtros" value="parcialmente">
                            <label for="busca_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label for="ergonomia_comentarios">Comentários sobre ergonomia e usabilidade:</label>
                    <textarea id="ergonomia_comentarios" name="ergonomia_comentarios" placeholder="Compartilhe sua experiência com a ergonomia e usabilidade do sistema..."></textarea>
                </div>
            </div>
            
            <!-- Seção 6: Segurança -->
            <div class="section">
                <h2>🔒 Segurança</h2>
                
                <div class="question">
                    <label>O sistema está protegendo adequadamente contra acessos indevidos?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="seguranca_sim" name="controle_acesso" value="sim" required>
                            <label for="seguranca_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="seguranca_nao" name="controle_acesso" value="não">
                            <label for="seguranca_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="seguranca_parcial" name="controle_acesso" value="parcialmente">
                            <label for="seguranca_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>As validações de dados estão protegendo adequadamente o sistema contra entradas inválidas?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="validacao_sim" name="validacao_dados" value="sim" required>
                            <label for="validacao_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="validacao_nao" name="validacao_dados" value="não">
                            <label for="validacao_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="validacao_parcial" name="validacao_dados" value="parcialmente">
                            <label for="validacao_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label for="seguranca_comentarios">Comentários sobre segurança:</label>
                    <textarea id="seguranca_comentarios" name="seguranca_comentarios" placeholder="Compartilhe sua experiência com a segurança do sistema..."></textarea>
                </div>
            </div>
            
            <!-- Seção 7: Performance -->
            <div class="section">
                <h2>⚡ Performance</h2>
                
                <div class="question">
                    <label>O sistema está respondendo de forma satisfatória?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="performance_sim" name="tempo_resposta" value="sim" required>
                            <label for="performance_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="performance_nao" name="tempo_resposta" value="não">
                            <label for="performance_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="performance_parcial" name="tempo_resposta" value="parcialmente">
                            <label for="performance_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label>Os dados estão mantendo sua consistência após todas as operações?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="consistencia_sim" name="consistencia_dados" value="sim" required>
                            <label for="consistencia_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="consistencia_nao" name="consistencia_dados" value="não">
                            <label for="consistencia_nao">Não</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="consistencia_parcial" name="consistencia_dados" value="parcialmente">
                            <label for="consistencia_parcial">Parcialmente</label>
                        </div>
                    </div>
                </div>
                
                <div class="question">
                    <label for="performance_comentarios">Comentários sobre performance:</label>
                    <textarea id="performance_comentarios" name="performance_comentarios" placeholder="Compartilhe sua experiência com a performance do sistema..."></textarea>
                </div>
            </div>
            
            <!-- Seção 8: Feedback Geral -->
            <div class="section">
                <h2>⭐ Feedback Geral</h2>
                
                <div class="question">
                    <label>Quais funcionalidades você gostaria que fossem adicionadas ao sistema?</label>
                    <textarea id="funcionalidades_adicionar" name="funcionalidades_adicionar" placeholder="Descreva funcionalidades que você gostaria de ver no sistema..."></textarea>
                </div>
                
                <div class="question">
                    <label>Quais funcionalidades existentes você gostaria que fossem aprimoradas?</label>
                    <textarea id="funcionalidades_aprimorar" name="funcionalidades_aprimorar" placeholder="Descreva funcionalidades existentes que você gostaria de ver aprimoradas..."></textarea>
                </div>
                
                <div class="question">
                    <label>Em uma escala de 1 a 10, como você avalia a facilidade de uso do sistema? <span class="required">*</span></label>
                    <input type="range" id="facilidade_uso" name="facilidade_uso" min="1" max="10" value="5" required>
                    <div class="rating-scale">
                        <span>1 - Muito difícil</span>
                        <span id="facilidade_uso_value">5</span>
                        <span>10 - Muito fácil</span>
                    </div>
                </div>
                
                <div class="question">
                    <label>Em uma escala de 1 a 10, como você avalia a eficiência do sistema para realizar suas tarefas? <span class="required">*</span></label>
                    <input type="range" id="eficiencia_sistema" name="eficiencia_sistema" min="1" max="10" value="5" required>
                    <div class="rating-scale">
                        <span>1 - Muito ineficiente</span>
                        <span id="eficiencia_sistema_value">5</span>
                        <span>10 - Muito eficiente</span>
                    </div>
                </div>
                
                <div class="question">
                    <label>Em uma escala de 1 a 10, como você avalia a aparência e organização do sistema? <span class="required">*</span></label>
                    <input type="range" id="aparencia_sistema" name="aparencia_sistema" min="1" max="10" value="5" required>
                    <div class="rating-scale">
                        <span>1 - Muito ruim</span>
                        <span id="aparencia_sistema_value">5</span>
                        <span>10 - Muito boa</span>
                    </div>
                </div>
                
                <div class="question">
                    <label for="comentarios_finais">Comentários finais:</label>
                    <textarea id="comentarios_finais" name="comentarios_finais" placeholder="Compartilhe seus comentários finais sobre o sistema..."></textarea>
                </div>
                
                <div class="question">
                    <label for="problemas_encontrados">Você encontrou algum problema que não foi coberto pelos testes anteriores?</label>
                    <textarea id="problemas_encontrados" name="problemas_encontrados" placeholder="Descreva quaisquer problemas não cobertos..."></textarea>
                </div>
                
                <div class="question">
                    <label for="sugestoes_especificas">Você tem alguma sugestão específica para melhorar a experiência do usuário?</label>
                    <textarea id="sugestoes_especificas" name="sugestoes_especificas" placeholder="Compartilhe suas sugestões específicas..."></textarea>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Enviar Feedback</button>
        </form>
    </div>
    
    <script>
        // Atualizar valores dos sliders em tempo real
        document.getElementById('facilidade_uso').addEventListener('input', function() {
            document.getElementById('facilidade_uso_value').textContent = this.value;
        });
        
        document.getElementById('eficiencia_sistema').addEventListener('input', function() {
            document.getElementById('eficiencia_sistema_value').textContent = this.value;
        });
        
        document.getElementById('aparencia_sistema').addEventListener('input', function() {
            document.getElementById('aparencia_sistema_value').textContent = this.value;
        });
        
        // Preencher valores iniciais
        document.getElementById('facilidade_uso_value').textContent = document.getElementById('facilidade_uso').value;
        document.getElementById('eficiencia_sistema_value').textContent = document.getElementById('eficiencia_sistema').value;
        document.getElementById('aparencia_sistema_value').textContent = document.getElementById('aparencia_sistema').value;
    </script>
</body>
</html>
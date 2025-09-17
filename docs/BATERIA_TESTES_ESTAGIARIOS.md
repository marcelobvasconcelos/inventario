# Bateria de Testes - Sistema de Inventário
## Para Estagiários e Testadores

---

## **INSTRUÇÕES GERAIS**

### Antes de Começar
1. **Ambiente:** Use dados de teste, nunca dados reais
2. **Navegador:** Teste em Chrome, Firefox e Edge
3. **Documentação:** Anote todos os erros encontrados
4. **Usuários de Teste:** Use as contas fornecidas pelo supervisor

### Como Reportar Problemas
- **Título:** Descrição clara do problema
- **Passos:** Como reproduzir o erro
- **Resultado Esperado:** O que deveria acontecer
- **Resultado Atual:** O que realmente aconteceu
- **Navegador:** Qual navegador foi usado

---

## **TESTE 1: AUTENTICAÇÃO E USUÁRIOS**

### T1.1 - Login e Logout
**Objetivo:** Verificar funcionamento básico de acesso
- [ ] Fazer login com usuário válido
- [ ] Tentar login com senha incorreta
- [ ] Tentar login com usuário inexistente
- [ ] Fazer logout
- [ ] Tentar acessar página após logout

### T1.2 - Registro de Usuário
**Objetivo:** Testar criação de novas contas
- [ ] Registrar novo usuário com dados válidos
- [ ] Tentar registrar com email já existente
- [ ] Registrar com campos obrigatórios vazios
- [ ] Verificar se conta fica "pendente"

### T1.3 - Recuperação de Senha
**Objetivo:** Testar processo de recuperação
- [ ] Solicitar nova senha com dados válidos
- [ ] Tentar com email inexistente
- [ ] Verificar se administrador recebe solicitação

---

## **TESTE 2: GERENCIAMENTO DE ITENS**

### T2.1 - Cadastro de Itens
**Objetivo:** Testar criação de novos itens
- [ ] Cadastrar item com todos os campos obrigatórios
- [ ] Tentar cadastrar sem nome
- [ ] Cadastrar item sem categoria
- [ ] Cadastrar item sem local
- [ ] Verificar se número patrimonial é gerado

### T2.2 - Edição de Itens
**Objetivo:** Testar alteração de itens existentes
- [ ] Editar nome do item
- [ ] Alterar categoria
- [ ] Mudar responsável
- [ ] Alterar valor
- [ ] Salvar sem fazer alterações

### T2.3 - Exclusão de Itens
**Objetivo:** Testar remoção de itens
- [ ] Excluir item como administrador
- [ ] Tentar excluir como gestor (item de outro)
- [ ] Verificar se item vai para lixeira
- [ ] Tentar excluir item inexistente

### T2.4 - Busca e Filtros
**Objetivo:** Testar funcionalidades de pesquisa
- [ ] Buscar item por nome
- [ ] Filtrar por categoria
- [ ] Filtrar por local
- [ ] Filtrar por responsável
- [ ] Buscar termo inexistente

---

## **TESTE 3: GERENCIAMENTO DE LOCAIS**

### T3.1 - Cadastro de Locais
**Objetivo:** Testar criação de locais
- [ ] Cadastrar local com nome válido
- [ ] Tentar cadastrar sem nome
- [ ] Cadastrar local com nome duplicado
- [ ] Adicionar descrição ao local

### T3.2 - Solicitação de Locais
**Objetivo:** Testar processo de solicitação
- [ ] Solicitar novo local como gestor
- [ ] Preencher justificativa
- [ ] Verificar se administrador recebe solicitação
- [ ] Aprovar solicitação como administrador
- [ ] Rejeitar solicitação como administrador

---

## **TESTE 4: MOVIMENTAÇÕES**

### T4.1 - Registro de Movimentações
**Objetivo:** Testar transferências de itens
- [ ] Registrar movimentação simples
- [ ] Transferir item para outro responsável
- [ ] Mudar local do item
- [ ] Registrar movimentação em lote
- [ ] Adicionar observações

### T4.2 - Confirmação de Recebimento
**Objetivo:** Testar processo de confirmação
- [ ] Confirmar recebimento de item
- [ ] Recusar recebimento com justificativa
- [ ] Verificar notificação para novo responsável
- [ ] Responder como administrador à recusa

---

## **TESTE 5: MÓDULO ALMOXARIFADO**

### T5.1 - Empenhos
**Objetivo:** Testar criação e gestão de empenhos
- [ ] Criar empenho com valor válido
- [ ] Editar valor do empenho
- [ ] Verificar cálculo de saldo
- [ ] Tentar criar empenho sem valor

### T5.2 - Notas Fiscais
**Objetivo:** Testar registro de notas fiscais
- [ ] Registrar nota fiscal vinculada a empenho
- [ ] Preencher dados do fornecedor
- [ ] Verificar desconto no saldo do empenho
- [ ] Tentar registrar valor maior que saldo
- [ ] Editar nota fiscal existente

### T5.3 - Materiais
**Objetivo:** Testar cadastro e controle de materiais
- [ ] Cadastrar novo material
- [ ] Selecionar categoria existente
- [ ] Definir unidade de medida
- [ ] Verificar estoque inicial zero

### T5.4 - Entrada de Materiais
**Objetivo:** Testar entrada no estoque
- [ ] Dar entrada de material com nota fiscal
- [ ] Verificar aumento do estoque
- [ ] Verificar desconto no saldo da nota
- [ ] Tentar entrada maior que saldo disponível

### T5.5 - Controle de Estoque
**Objetivo:** Testar movimentações de estoque
- [ ] Registrar saída de material
- [ ] Ajustar estoque (zerar)
- [ ] Verificar histórico de movimentações
- [ ] Tentar saída maior que estoque

---

## **TESTE 6: PERMISSÕES E SEGURANÇA**

### T6.1 - Perfil Visualizador
**Objetivo:** Verificar limitações do visualizador
- [ ] Tentar cadastrar item (deve ser bloqueado)
- [ ] Tentar editar item (deve ser bloqueado)
- [ ] Visualizar apenas itens próprios
- [ ] Acessar relatórios permitidos

### T6.2 - Perfil Gestor
**Objetivo:** Verificar permissões do gestor
- [ ] Cadastrar item próprio
- [ ] Tentar editar item de outro gestor
- [ ] Solicitar novo local
- [ ] Confirmar/recusar movimentações

### T6.3 - Perfil Administrador
**Objetivo:** Verificar acesso total
- [ ] Acessar todas as funcionalidades
- [ ] Gerenciar usuários
- [ ] Aprovar solicitações
- [ ] Acessar lixeira

---

## **TESTE 7: INTERFACE E USABILIDADE**

### T7.1 - Navegação
**Objetivo:** Testar facilidade de uso
- [ ] Navegar entre todas as páginas
- [ ] Usar menu principal
- [ ] Testar botões "Voltar"
- [ ] Verificar breadcrumbs

### T7.2 - Responsividade
**Objetivo:** Testar em diferentes tamanhos de tela
- [ ] Testar em desktop (1920x1080)
- [ ] Testar em tablet (768x1024)
- [ ] Testar em mobile (375x667)
- [ ] Verificar menus em telas pequenas

### T7.3 - Mensagens e Feedback
**Objetivo:** Verificar comunicação com usuário
- [ ] Verificar mensagens de sucesso
- [ ] Verificar mensagens de erro
- [ ] Testar notificações
- [ ] Verificar tooltips e ajudas

---

## **TESTE 8: RELATÓRIOS E CONSULTAS**

### T8.1 - Relatórios Básicos
**Objetivo:** Testar geração de relatórios
- [ ] Gerar relatório de itens por local
- [ ] Gerar relatório de itens por responsável
- [ ] Exportar relatório em PDF
- [ ] Exportar relatório em Excel

### T8.2 - Dashboard
**Objetivo:** Verificar informações do painel
- [ ] Verificar contadores de itens
- [ ] Verificar gráficos (se houver)
- [ ] Testar links rápidos
- [ ] Verificar notificações pendentes

---

## **TESTE 9: CENÁRIOS ESPECIAIS**

### T9.1 - Lixeira
**Objetivo:** Testar sistema de recuperação
- [ ] Acessar itens excluídos
- [ ] Restaurar item da lixeira
- [ ] Verificar histórico após restauração

### T9.2 - Dados em Massa
**Objetivo:** Testar com grande volume
- [ ] Cadastrar 50+ itens
- [ ] Fazer movimentação em lote
- [ ] Testar performance da busca
- [ ] Verificar paginação

### T9.3 - Situações de Erro
**Objetivo:** Testar tratamento de erros
- [ ] Tentar acessar página inexistente
- [ ] Submeter formulário com dados inválidos
- [ ] Testar com conexão lenta
- [ ] Simular erro de banco de dados

---

## **CHECKLIST FINAL**

### Antes de Entregar os Testes
- [ ] Todos os testes foram executados
- [ ] Problemas foram documentados
- [ ] Screenshots dos erros foram capturadas
- [ ] Sugestões de melhoria foram anotadas
- [ ] Relatório final foi preparado

### Critérios de Aprovação
- [ ] Login/logout funcionam corretamente
- [ ] Cadastros básicos funcionam
- [ ] Permissões são respeitadas
- [ ] Não há erros críticos
- [ ] Interface é intuitiva

---

## **MODELO DE RELATÓRIO DE BUG**

```
TÍTULO: [Descrição curta do problema]

PRIORIDADE: [ ] Crítica [ ] Alta [ ] Média [ ] Baixa

PASSOS PARA REPRODUZIR:
1. 
2. 
3. 

RESULTADO ESPERADO:


RESULTADO ATUAL:


NAVEGADOR/VERSÃO:


OBSERVAÇÕES ADICIONAIS:


```

---

**Tempo estimado para execução completa: 8-12 horas**
**Recomendação: Dividir em sessões de 2-3 horas**
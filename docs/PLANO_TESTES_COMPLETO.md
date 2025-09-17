# Plano de Testes Abrangente - Sistema de Inventário e Almoxarifado

## Visão Geral
Este documento contém uma lista completa de testes para verificar a funcionalidade, usabilidade e precisão dos cálculos em todo o sistema, abrangendo tanto o módulo legado de inventário quanto o novo módulo de almoxarifado.

## Instruções Gerais
- Execute cada teste listado abaixo em ordem sequencial
- Documente qualquer problema encontrado durante os testes
- Forneça feedback sobre a usabilidade e ergonomia de cada funcionalidade
- Verifique a precisão dos cálculos em todos os testes que envolvem valores numéricos

---

## 1. TESTES DE AUTENTICAÇÃO E PERFIS DE USUÁRIO

### 1.1. Acesso e Autenticação
**[Administrador]**
- [ ] Faça login como administrador e verifique se todos os menus e funcionalidades estão disponíveis
- [ ] Tente acessar diretamente URLs restritas de outros perfis (ex: /almoxarifado/) e verifique se o acesso é negado adequadamente

**[Almoxarife]**
- [ ] Faça login como almoxarife e verifique se apenas as funcionalidades permitidas estão disponíveis
- [ ] Tente acessar funcionalidades administrativas e verifique se o acesso é negado

**[Gestor]**
- [ ] Faça login como gestor e verifique se pode acessar o almoxarifado e fazer requisições
- [ ] Tente acessar funcionalidades restritas a administradores e almoxarifes

**[Visualizador]**
- [ ] Faça login como visualizador e verifique se possui acesso apenas às funcionalidades de visualização
- [ ] Tente realizar operações de edição e verifique se são bloqueadas

### 1.2. Perguntas sobre Autenticação
1. A navegação entre perfis está clara e intuitiva?
2. Você conseguiu identificar facilmente quais funcionalidades estão disponíveis para o seu perfil?
3. Houve alguma situação em que você esperava ter acesso a uma funcionalidade, mas não teve?

---

## 2. TESTES DO MÓDULO LEGADO DE INVENTÁRIO

### 2.1. Cadastro e Gerenciamento de Itens
**[Administrador]**
- [ ] Cadastre um novo item com código único, preencha todos os campos e verifique se foi salvo corretamente
- [ ] Edite um item existente e verifique se as alterações foram salvas
- [ ] Exclua um item e verifique se foi movido para a lixeira
- [ ] Restaure um item da lixeira e verifique se voltou para a listagem principal

### 2.2. Movimentações de Itens
**[Administrador]**
- [ ] Registre uma movimentação de item entre locais diferentes
- [ ] Verifique se o saldo do item foi atualizado corretamente nos locais de origem e destino
- [ ] Confirme uma movimentação pendente e verifique se o status foi atualizado

**[Gestor]**
- [ ] Receba uma notificação de movimentação e confirme-a
- [ ] Recuse uma movimentação e forneça uma justificativa

### 2.3. Locais e Responsáveis
**[Administrador]**
- [ ] Crie um novo local e verifique se foi salvo corretamente
- [ ] Atribua um item a um local e verifique se o saldo foi atualizado
- [ ] Atribua um responsável por um item e verifique se a informação foi registrada

### 2.4. Relatórios e Estatísticas
**[Administrador]**
- [ ] Gere um relatório de itens em PDF e verifique se todas as informações estão corretas
- [ ] Verifique se os totais nos dashboards estão calculados corretamente

### 2.5. Perguntas sobre o Módulo Legado
1. As telas de cadastro e edição são intuitivas e fáceis de usar?
2. Os campos necessários estão claramente identificados?
3. Os cálculos de saldo estão corretos após movimentações?
4. As notificações são claras e informativas?
5. Há alguma funcionalidade que você sente falta ou que poderia ser aprimorada?

---

## 3. TESTES DO MÓDULO DE ALMOXARIFADO

### 3.1. Cadastro e Gerenciamento de Materiais
**[Administrador]**
- [ ] Cadastre um novo material com todas as informações necessárias
- [ ] Edite um material existente e verifique se as alterações foram salvas
- [ ] Verifique se o material aparece corretamente na listagem com todos os detalhes

**[Pergunta]** O processo de cadastro de materiais é intuitivo? Alguma informação importante está faltando?

### 3.2. Gestão de Empenhos e Notas Fiscais
**[Administrador]**
- [ ] Cadastre um novo empenho com fornecedor, valor e demais informações
- [ ] Cadastre uma nota fiscal vinculada ao empenho criado
- [ ] Verifique se o saldo do empenho foi atualizado corretamente após a criação da nota fiscal

**[Pergunta]** A vinculação entre empenhos e notas fiscais está clara? Há alguma informação que deveria ser adicionada?

### 3.3. Entrada de Materiais
**[Administrador]**
- [ ] Registre uma entrada de material vinculada a uma nota fiscal
- [ ] Verifique se o estoque do material foi atualizado corretamente
- [ ] Verifique se o saldo da nota fiscal foi atualizado corretamente
- [ ] Verifique se o saldo do empenho foi atualizado corretamente

**[Cálculo]** Registre uma entrada com:
- Material A: 10 unidades a R$ 5,00 cada (total R$ 50,00)
- Material B: 5 unidades a R$ 8,00 cada (total R$ 40,00)
- Valor total da entrada: R$ 90,00

Verifique se:
- O estoque dos materiais foi atualizado corretamente (+10 unidades para Material A, +5 unidades para Material B)
- O saldo da nota fiscal foi reduzido em R$ 90,00
- O saldo do empenho foi reduzido em R$ 90,00
- O histórico de alterações de saldo do empenho foi registrado

**[Pergunta]** O processo de registro de entradas está claro? Os saldos estão sendo atualizados corretamente?

### 3.4. Requisições de Materiais
**[Gestor/Almoxarife]**
- [ ] Crie uma nova requisição de materiais especificando quantidade e justificativa
- [ ] Verifique se a requisição aparece na fila de aprovações
- [ ] Como administrador, aprove a requisição e verifique se o estoque foi atualizado
- [ ] Como gestor, verifique se recebeu a notificação de aprovação

**[Cálculo]** Crie uma requisição com:
- Material A: 3 unidades (estoque disponível: 10 unidades)
- Material B: 2 unidades (estoque disponível: 5 unidades)

Verifique se:
- A requisição foi criada com status "pendente"
- Após aprovação, o estoque foi reduzido corretamente (Material A: 7 unidades restantes, Material B: 3 unidades restantes)
- O histórico de movimentações foi registrado corretamente

**[Pergunta]** O processo de requisição é intuitivo? As notificações são claras e oportunas?

### 3.5. Controle de Estoque
**[Administrador/Almoxarife]**
- [ ] Verifique se os níveis de estoque mínimo estão sendo respeitados
- [ ] Verifique se os alertas de estoque baixo estão funcionando corretamente
- [ ] Realize um ajuste de estoque (entrada ou saída) e verifique se foi registrado

**[Cálculo]** Verifique os seguintes cenários:
1. Material com estoque mínimo de 5 unidades e estoque atual de 3 unidades - deve mostrar alerta
2. Material com estoque atual de 0 unidades - deve mostrar como "sem estoque"
3. Material com estoque atual de 10 unidades e estoque mínimo de 5 unidades - deve mostrar como "normal"

**[Pergunta]** Os níveis de alerta de estoque estão adequados? Há alguma informação adicional que seria útil?

### 3.6. Relatórios e Dashboards
**[Administrador/Almoxarife]**
- [ ] Gere um relatório de movimentações de materiais e verifique a precisão das informações
- [ ] Verifique se os dashboards estão mostrando informações atualizadas e corretas
- [ ] Gere um relatório em PDF e verifique se todas as informações estão presentes

**[Cálculo]** Verifique se:
- O valor total do estoque está calculado corretamente (estoque atual × valor unitário)
- Os totais de entradas e saídas estão batendo com os registros
- As estatísticas de uso estão corretas

**[Pergunta]** Os relatórios fornecem as informações necessárias? Alguma informação importante está faltando?

---

## 4. TESTES DE INTEGRAÇÃO ENTRE MÓDULOS

### 4.1. Vinculação de Materiais a Notas Fiscais
**[Administrador]**
- [ ] Verifique se os materiais registrados estão corretamente vinculados às notas fiscais
- [ ] Verifique se é possível visualizar quais materiais pertencem a quais notas fiscais

**[Pergunta]** A vinculação direta de materiais às notas fiscais facilita a rastreabilidade? Há alguma melhoria que poderia ser feita?

### 4.2. Histórico de Alterações de Saldo dos Empenhos
**[Administrador]**
- [ ] Verifique se todas as movimentações de saldo dos empenhos estão registradas no histórico
- [ ] Verifique se os valores registrados estão corretos e consistentes

**[Cálculo]** Verifique se para cada entrada de material:
- Uma entrada foi registrada no histórico de alterações de saldo
- O valor da alteração corresponde ao valor da entrada
- O saldo anterior e novo estão calculados corretamente

**[Pergunta]** O histórico de alterações de saldo é útil e informativo? Alguma informação está faltando?

### 4.3. Auditoria de Criação de Materiais
**[Administrador]**
- [ ] Verifique se os materiais registrados contêm informações de data e usuário de criação
- [ ] Verifique se essas informações estão corretas e consistentes

**[Pergunta]** As informações de auditoria são úteis? Há alguma informação adicional que seria interessante ter?

---

## 5. TESTES DE ERGONOMIA E USABILIDADE

### 5.1. Navegação e Interface
**[Todos os Perfis]**
- [ ] Avalie a facilidade de navegação entre as diferentes seções do sistema
- [ ] Verifique se os menus e botões estão posicionados de forma intuitiva
- [ ] Avalie a responsividade do sistema em diferentes tamanhos de tela

**[Pergunta]** A interface do sistema é intuitiva e fácil de usar? Algum elemento da interface está confuso ou mal posicionado?

### 5.2. Formulários e Validações
**[Todos os Perfis]**
- [ ] Preencha formulários com dados inválidos e verifique se as mensagens de erro são claras
- [ ] Verifique se os campos obrigatórios estão claramente identificados
- [ ] Avalie a facilidade de preenchimento dos formulários

**[Pergunta]** Os formulários são fáceis de preencher? As mensagens de erro são claras e ajudam a corrigir os problemas?

### 5.3. Busca e Filtros
**[Todos os Perfis]**
- [ ] Utilize a função de busca em diferentes telas e verifique se os resultados são relevantes
- [ ] Aplique filtros e verifique se os resultados são filtrados corretamente
- [ ] Limpe os filtros e verifique se a listagem retorna ao estado original

**[Pergunta]** As funções de busca e filtro são eficazes e fáceis de usar? Alguma opção de filtro está faltando?

---

## 6. TESTES DE SEGURANÇA

### 6.1. Controle de Acesso
**[Todos os Perfis]**
- [ ] Tente acessar funcionalidades que não deveriam estar disponíveis para o seu perfil
- [ ] Verifique se as tentativas de acesso indevido são registradas e bloqueadas
- [ ] Tente manipular URLs para acessar informações restritas

**[Pergunta]** Você conseguiu acessar alguma funcionalidade que não deveria estar disponível para o seu perfil?

### 6.2. Validação de Dados
**[Todos os Perfis]**
- [ ] Tente inserir dados inválidos em campos numéricos
- [ ] Tente inserir scripts maliciosos em campos de texto
- [ ] Verifique se as validações estão funcionando corretamente

**[Pergunta]** As validações de dados estão protegendo adequadamente o sistema contra entradas inválidas?

---

## 7. TESTES DE PERFORMANCE

### 7.1. Tempo de Resposta
**[Todos os Perfis]**
- [ ] Meça o tempo de carregamento das principais páginas
- [ ] Verifique se operações complexas (como geração de relatórios) estão dentro de limites aceitáveis
- [ ] Teste o sistema com múltiplos usuários simultâneos

**[Pergunta]** O sistema está respondendo de forma satisfatória? Há alguma operação que está demorando mais do que o esperado?

### 7.2. Consistência de Dados
**[Todos os Perfis]**
- [ ] Realize operações repetidas e verifique se os resultados são consistentes
- [ ] Verifique se os dados permanecem consistentes após operações de CRUD
- [ ] Teste a integridade referencial após operações complexas

**[Pergunta]** Os dados estão mantendo sua consistência após todas as operações? Você notou alguma inconsistência nos dados?

---

## 8. FEEDBACK GERAL

### 8.1. Sugestões de Melhoria
1. Quais funcionalidades você gostaria que fossem adicionadas ao sistema?
2. Quais funcionalidades existentes você gostaria que fossem aprimoradas?
3. Há alguma tarefa que você realiza frequentemente que poderia ser facilitada?

### 8.2. Avaliação Geral
1. Em uma escala de 1 a 10, como você avalia a facilidade de uso do sistema?
2. Em uma escala de 1 a 10, como você avalia a eficiência do sistema para realizar suas tarefas?
3. Em uma escala de 1 a 10, como você avalia a aparência e organização do sistema?

### 8.3. Comentários Finais
1. Há algo mais que você gostaria de comentar sobre o sistema?
2. Você encontrou algum problema que não foi coberto pelos testes anteriores?
3. Você tem alguma sugestão específica para melhorar a experiência do usuário?

---

## CONSIDERAÇÕES FINAIS

Este plano de testes deve ser executado por usuários reais de todos os perfis para garantir que o sistema atenda às necessidades de todos os stakeholders. Os resultados dos testes devem ser compilados e analisados para identificar áreas que necessitam de melhorias ou correções.

Após a execução dos testes, uma reunião deve ser realizada com todos os participantes para discutir os resultados e definir as próximas etapas de desenvolvimento.
# INSTRUÇÕES PARA REALIZAÇÃO DOS TESTES
## Sistema de Inventário e Almoxarifado

### Data: 16/09/2025
### Versão: 1.6.0

---

## 🎯 OBJETIVO DOS TESTES

O objetivo destes testes é validar as melhorias implementadas no módulo de almoxarifado, garantindo que as funcionalidades estejam funcionando corretamente e que a experiência do usuário seja otimizada. Seu feedback é fundamental para identificarmos pontos de melhoria e corrigirmos eventuais problemas.

---

## 📋 COMO REALIZAR OS TESTES

### 1. Acesse o Sistema
- Acesse o sistema através do endereço: http://localhost/inventario
- Faça login com as credenciais fornecidas para o seu perfil de usuário

### 2. Execute os Testes Conforme seu Perfil
- **Administrador**: Execute todos os testes marcados com [Administrador]
- **Almoxarife**: Execute todos os testes marcados com [Almoxarife]
- **Gestor**: Execute todos os testes marcados com [Gestor]
- **Visualizador**: Execute todos os testes marcados com [Visualizador]

### 3. Preencha o Formulário de Feedback
- Após concluir os testes, acesse o formulário em: http://localhost/inventario/feedback_testes.php
- Preencha todas as informações solicitadas
- Seja honesto e detalhado em seus comentários

---

## 🧪 GUIA DE TESTES POR PERFIL

### PERFIL: ADMINISTRADOR

#### Teste 1: Vinculação Direta de Materiais às Notas Fiscais
**Objetivo**: Verificar se os materiais estão corretamente vinculados às notas fiscais

**Passos**:
1. Acesse o módulo de almoxarifado
2. Crie um novo material e vincule-o a uma nota fiscal existente
3. Verifique se o material aparece na listagem com a nota fiscal correta
4. Edite o material e verifique se a nota fiscal pode ser alterada
5. Acesse os detalhes do material e verifique se a nota fiscal está corretamente exibida

**Critérios de Sucesso**:
- ✅ Material criado com nota fiscal vinculada
- ✅ Nota fiscal exibida corretamente na listagem
- ✅ Nota fiscal pode ser editada
- ✅ Nota fiscal exibida nos detalhes do material

#### Teste 2: Campos de Auditoria na Tabela de Materiais
**Objetivo**: Verificar se as informações de auditoria estão sendo registradas corretamente

**Passos**:
1. Crie um novo material e verifique se as informações de auditoria são registradas
2. Edite o material e verifique se as informações de auditoria são atualizadas
3. Acesse os detalhes do material e verifique se as informações de auditoria estão corretas
4. Verifique se o campo "data_criacao" foi preenchido automaticamente
5. Verifique se o campo "usuario_criacao" foi preenchido com o ID do usuário logado

**Critérios de Sucesso**:
- ✅ Data e hora de criação registradas automaticamente
- ✅ ID do usuário de criação registrado corretamente
- ✅ Informações de auditoria exibidas corretamente nos detalhes
- ✅ Informações de auditoria atualizadas após edições

#### Teste 3: Histórico de Alterações de Saldo dos Empenhos
**Objetivo**: Verificar se o histórico de alterações de saldo dos empenhos está funcionando corretamente

**Passos**:
1. Acesse o histórico de alterações de saldo dos empenhos através do menu do almoxarifado
2. Verifique se todas as movimentações estão registradas no histórico
3. Registre uma nova entrada de material e verifique se uma nova entrada foi adicionada ao histórico
4. Verifique se os valores registrados estão corretos
5. Verifique se o tipo de alteração está correto (entrada/saída/ajuste)
6. Verifique se o usuário responsável está correto
7. Verifique se a descrição da alteração é informativa

**Critérios de Sucesso**:
- ✅ Todas as movimentações registradas no histórico
- ✅ Nova entrada adicionada após registro de entrada de material
- ✅ Valores registrados corretamente
- ✅ Tipo de alteração correto
- ✅ Usuário responsável correto
- ✅ Descrição informativa

#### Teste 4: Integração entre Módulos
**Objetivo**: Verificar se a integração entre os módulos está funcionando corretamente

**Passos**:
1. Registre uma entrada de material e verifique se:
   - O estoque do material foi atualizado corretamente
   - O saldo da nota fiscal foi atualizado corretamente
   - O saldo do empenho foi atualizado corretamente
   - Uma entrada foi registrada no histórico de alterações de saldo
2. Verifique se os materiais registrados contêm informações de data e usuário de criação
3. Verifique se os materiais registrados estão corretamente vinculados às notas fiscais

**Critérios de Sucesso**:
- ✅ Estoque do material atualizado corretamente
- ✅ Saldo da nota fiscal atualizado corretamente
- ✅ Saldo do empenho atualizado corretamente
- ✅ Entrada registrada no histórico de alterações de saldo
- ✅ Informações de auditoria registradas corretamente
- ✅ Materiais corretamente vinculados às notas fiscais

### PERFIL: ALMOXARIFE

#### Teste 1: Gestão de Materiais
**Objetivo**: Verificar se as funcionalidades de gestão de materiais estão acessíveis e funcionando

**Passos**:
1. Acesse o módulo de almoxarifado
2. Verifique se pode visualizar a listagem de materiais
3. Verifique se pode visualizar os detalhes de um material
4. Verifique se pode realizar buscas e filtros na listagem de materiais
5. Verifique se pode acessar o histórico de alterações de saldo dos empenhos

**Critérios de Sucesso**:
- ✅ Listagem de materiais acessível
- ✅ Detalhes de materiais acessíveis
- ✅ Buscas e filtros funcionando
- ✅ Histórico de alterações acessível

#### Teste 2: Registro de Entradas
**Objetivo**: Verificar se o registro de entradas de materiais está funcionando corretamente

**Passos**:
1. Acesse a função de registro de entrada de materiais
2. Registre uma entrada de material
3. Verifique se o estoque do material foi atualizado corretamente
4. Verifique se o saldo da nota fiscal foi atualizado corretamente
5. Verifique se o saldo do empenho foi atualizado corretamente
6. Verifique se uma entrada foi registrada no histórico de alterações de saldo

**Critérios de Sucesso**:
- ✅ Registro de entrada acessível
- ✅ Estoque do material atualizado corretamente
- ✅ Saldo da nota fiscal atualizado corretamente
- ✅ Saldo do empenho atualizado corretamente
- ✅ Entrada registrada no histórico

#### Teste 3: Aprovação de Requisições
**Objetivo**: Verificar se a aprovação de requisições está funcionando corretamente

**Passos**:
1. Verifique se recebe notificações de requisições pendentes
2. Acesse uma requisição pendente
3. Aprove a requisição
4. Verifique se o estoque foi atualizado corretamente
5. Verifique se o usuário requisitante recebeu notificação de aprovação

**Critérios de Sucesso**:
- ✅ Notificações de requisições recebidas
- ✅ Aprovação de requisições possível
- ✅ Estoque atualizado corretamente
- ✅ Notificação de aprovação enviada ao usuário

### PERFIL: GESTOR

#### Teste 1: Criação de Requisições
**Objetivo**: Verificar se a criação de requisições está funcionando corretamente

**Passos**:
1. Acesse o módulo de almoxarifado
2. Crie uma nova requisição de materiais
3. Verifique se a requisição foi criada com status "pendente"
4. Verifique se recebeu confirmação da criação da requisição

**Critérios de Sucesso**:
- ✅ Criação de requisições acessível
- ✅ Requisição criada com status correto
- ✅ Confirmação de criação recebida

#### Teste 2: Acompanhamento de Requisições
**Objetivo**: Verificar se o acompanhamento de requisições está funcionando corretamente

**Passos**:
1. Acesse a listagem de requisições
2. Verifique se pode visualizar suas requisições
3. Verifique o status das requisições
4. Verifique se recebe notificações de alterações de status

**Critérios de Sucesso**:
- ✅ Listagem de requisições acessível
- ✅ Visualização de requisições própria
- ✅ Status das requisições visíveis
- ✅ Notificações de alterações recebidas

### PERFIL: VISUALIZADOR

#### Teste 1: Visualização de Materiais
**Objetivo**: Verificar se a visualização de materiais está funcionando corretamente

**Passos**:
1. Acesse o módulo de almoxarifado
2. Verifique se pode visualizar a listagem de materiais
3. Verifique se pode visualizar os detalhes de um material
4. Verifique se pode realizar buscas e filtros na listagem de materiais

**Critérios de Sucesso**:
- ✅ Listagem de materiais acessível
- ✅ Detalhes de materiais acessíveis
- ✅ Buscas e filtros funcionando

#### Teste 2: Visualização de Histórico
**Objetivo**: Verificar se a visualização do histórico de alterações está funcionando

**Passos**:
1. Acesse o histórico de alterações de saldo dos empenhos
2. Verifique se pode visualizar as movimentações registradas
3. Verifique se as informações exibidas são claras e informativas

**Critérios de Sucesso**:
- ✅ Histórico acessível
- ✅ Movimentações visíveis
- ✅ Informações claras e informativas

---

## 📊 TESTES DE CÁLCULO ESPECÍFICOS

### Teste de Cálculo 1: Entrada de Materiais
**Objetivo**: Verificar a precisão dos cálculos ao registrar entrada de materiais

**Passos**:
1. Registre uma entrada com:
   - Material A: 10 unidades a R$ 5,00 cada (total R$ 50,00)
   - Material B: 5 unidades a R$ 8,00 cada (total R$ 40,00)
   - Valor total da entrada: R$ 90,00

2. Verifique se:
   - ✅ O estoque dos materiais foi atualizado corretamente (+10 unidades para Material A, +5 unidades para Material B)
   - ✅ O saldo da nota fiscal foi reduzido em R$ 90,00
   - ✅ O saldo do empenho foi reduzido em R$ 90,00
   - ✅ O histórico de alterações de saldo do empenho foi registrado

### Teste de Cálculo 2: Requisição de Materiais
**Objetivo**: Verificar a precisão dos cálculos ao registrar requisições de materiais

**Passos**:
1. Crie uma requisição com:
   - Material A: 3 unidades (estoque disponível: 10 unidades)
   - Material B: 2 unidades (estoque disponível: 5 unidades)

2. Verifique se:
   - ✅ A requisição foi criada com status "pendente"
   - ✅ Após aprovação, o estoque foi reduzido corretamente (Material A: 7 unidades restantes, Material B: 3 unidades restantes)
   - ✅ O histórico de movimentações foi registrado corretamente

### Teste de Cálculo 3: Níveis de Alerta de Estoque
**Objetivo**: Verificar se os níveis de alerta de estoque estão funcionando corretamente

**Passos**:
1. Verifique os seguintes cenários:
   - Material com estoque mínimo de 5 unidades e estoque atual de 3 unidades - deve mostrar alerta
   - Material com estoque atual de 0 unidades - deve mostrar como "sem estoque"
   - Material com estoque atual de 10 unidades e estoque mínimo de 5 unidades - deve mostrar como "normal"

2. Verifique se:
   - ✅ Alertas são exibidos corretamente
   - ✅ Cores e ícones são apropriados para cada situação
   - ✅ Informações são claras e fáceis de entender

---

## 📈 TESTES DE RELATÓRIOS E DASHBOARDS

### Teste de Relatório 1: Movimentações de Materiais
**Objetivo**: Verificar a precisão das informações nos relatórios de movimentações

**Passos**:
1. Gere um relatório de movimentações de materiais
2. Verifique se:
   - ✅ Todas as movimentações estão incluídas
   - ✅ As datas estão corretas
   - ✅ As quantidades estão corretas
   - ✅ Os valores estão corretos
   - ✅ Os saldos estão calculados corretamente

### Teste de Relatório 2: Valor Total do Estoque
**Objetivo**: Verificar o cálculo do valor total do estoque

**Passos**:
1. Verifique se:
   - ✅ O valor total do estoque está calculado corretamente (estoque atual × valor unitário)
   - ✅ Os totais de entradas e saídas estão batendo com os registros
   - ✅ As estatísticas de uso estão corretas

### Teste de Dashboard 1: Informações Atualizadas
**Objetivo**: Verificar se os dashboards estão mostrando informações atualizadas

**Passos**:
1. Verifique se:
   - ✅ As informações nos dashboards estão atualizadas em tempo real
   - ✅ Os gráficos estão exibindo dados corretos
   - ✅ As estatísticas estão calculadas corretamente

---

## 🔒 TESTES DE SEGURANÇA

### Teste de Segurança 1: Controle de Acesso
**Objetivo**: Verificar se o controle de acesso está funcionando corretamente

**Passos**:
1. Tente acessar funcionalidades que não deveriam estar disponíveis para o seu perfil
2. Verifique se as tentativas de acesso indevido são registradas e bloqueadas
3. Tente manipular URLs para acessar informações restritas

**Critérios de Sucesso**:
- ✅ Acesso negado a funcionalidades restritas
- ✅ Tentativas registradas no log de segurança
- ✅ Manipulação de URLs bloqueada

### Teste de Segurança 2: Validação de Dados
**Objetivo**: Verificar se as validações de dados estão funcionando corretamente

**Passos**:
1. Tente inserir dados inválidos em campos numéricos
2. Tente inserir scripts maliciosos em campos de texto
3. Verifique se as validações estão funcionando corretamente

**Critérios de Sucesso**:
- ✅ Dados inválidos rejeitados
- ✅ Scripts maliciosos bloqueados
- ✅ Mensagens de erro claras

---

## ⚡ TESTES DE PERFORMANCE

### Teste de Performance 1: Tempo de Resposta
**Objetivo**: Verificar o tempo de resposta do sistema

**Passos**:
1. Meça o tempo de carregamento das principais páginas
2. Verifique se operações complexas (como geração de relatórios) estão dentro de limites aceitáveis
3. Teste o sistema com múltiplos usuários simultâneos

**Critérios de Sucesso**:
- ✅ Tempo de carregamento aceitável (< 3 segundos para páginas principais)
- ✅ Operações complexas dentro de limites (< 10 segundos)
- ✅ Boa performance com múltiplos usuários

### Teste de Performance 2: Consistência de Dados
**Objetivo**: Verificar a consistência de dados

**Passos**:
1. Realize operações repetidas e verifique se os resultados são consistentes
2. Verifique se os dados permanecem consistentes após operações de CRUD
3. Teste a integridade referencial após operações complexas

**Critérios de Sucesso**:
- ✅ Resultados consistentes em operações repetidas
- ✅ Dados mantêm consistência após operações CRUD
- ✅ Integridade referencial mantida

---

## 📝 INSTRUÇÕES PARA PREENCHIMENTO DO FORMULÁRIO

Ao preencher o formulário de feedback, por favor:

1. **Seja honesto e objetivo** em suas avaliações
2. **Forneça detalhes específicos** sobre problemas encontrados
3. **Inclua sugestões de melhoria** sempre que possível
4. **Destaque pontos positivos** também
5. **Complete todas as seções** relevantes para o seu perfil

---

## ❓ SUPORTE DURANTE OS TESTES

Caso encontre problemas ou tenha dúvidas durante os testes, entre em contato com:

- **Desenvolvedor**: [Seu nome]
- **E-mail**: [seu@email.com]
- **Telefone**: [seu telefone]

---

## 🙏 AGRADECIMENTO

Agradecemos antecipadamente por dedicar seu tempo para realizar estes testes. Seu feedback é fundamental para garantir a qualidade e usabilidade do sistema.

Vamos juntos criar um sistema melhor!
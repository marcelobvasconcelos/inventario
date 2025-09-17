# Módulo de Almoxarifado

## Visão Geral
O módulo de almoxarifado é uma extensão do sistema de inventário que permite gerenciar produtos, requisições e estoque de materiais. Ele oferece um fluxo completo de solicitação, aprovação, agendamento e recebimento de materiais com interface moderna semelhante ao Gmail.

## Estrutura de Tabelas

### almoxarifado_materiais
- `id`: Identificador único do material
- `codigo`: Código único do material
- `nome`: Nome do material
- `descricao`: Descrição detalhada do material
- `unidade_medida`: Unidade de medida (ex: kg, litros, unidades)
- `estoque_minimo`: Quantidade mínima recomendada em estoque
- `estoque_atual`: Quantidade atual em estoque
- `valor_unitario`: Valor unitário do material
- `categoria`: Categoria do material
- `data_cadastro`: Data de cadastro do material
- `status`: Status do material (ativo/inativo)
- `quantidade_maxima_requisicao`: Quantidade máxima permitida por requisição (sem justificativa)

### almoxarifado_requisicoes
- `id`: Identificador único da requisição
- `usuario_id`: ID do usuário que fez a requisição
- `local_id`: ID do local de destino (opcional)
- `data_requisicao`: Data e hora da requisição
- `status`: Status da requisição (pendente, aprovada, rejeitada, concluida)
- `status_notificacao`: Status da notificação (pendente, em_discussao, aprovada, rejeitada, agendada, concluida)
- `justificativa`: Justificativa da requisição (obrigatória apenas se algum item ultrapassar a quantidade máxima)
- `data_conclusao`: Data e hora da conclusão da requisição

### almoxarifado_requisicoes_itens
- `id`: Identificador único do item da requisição
- `requisicao_id`: ID da requisição
- `produto_id`: ID do produto solicitado
- `quantidade_solicitada`: Quantidade solicitada
- `quantidade_entregue`: Quantidade entregue

### almoxarifado_requisicoes_notificacoes
- `id`: Identificador único da notificação
- `requisicao_id`: ID da requisição associada
- `usuario_origem_id`: ID do usuário que originou a notificação
- `usuario_destino_id`: ID do usuário destinatário da notificação
- `tipo`: Tipo da notificação (nova_requisicao, resposta_admin, resposta_usuario, aprovada, rejeitada, agendamento, concluida)
- `mensagem`: Mensagem da notificação
- `status`: Status da notificação (pendente, lida, respondida, concluida)
- `data_criacao`: Data de criação da notificação
- `data_leitura`: Data de leitura da notificação
- `data_conclusao`: Data de conclusão da notificação

### almoxarifado_requisicoes_conversas
- `id`: Identificador único da mensagem
- `notificacao_id`: ID da notificação associada
- `usuario_id`: ID do usuário que enviou a mensagem
- `mensagem`: Conteúdo da mensagem
- `tipo_usuario`: Tipo do usuário (requisitante, administrador)
- `data_mensagem`: Data e hora da mensagem

### almoxarifado_movimentacoes
- `id`: Identificador único da movimentação
- `material_id`: ID do material movimentado
- `tipo`: Tipo da movimentação (entrada, saida)
- `quantidade`: Quantidade movimentada
- `saldo_anterior`: Saldo anterior à movimentação
- `saldo_atual`: Saldo após a movimentação
- `data_movimentacao`: Data e hora da movimentação
- `usuario_id`: ID do usuário responsável pela movimentação

## Perfis de Usuário
- **Administrador**: Acesso completo a todas as funcionalidades
- **Almoxarife**: Acesso às funcionalidades de almoxarifado
- **Visualizador**: Acesso para visualizar produtos e criar requisições
- **Gestor**: Acesso para visualizar produtos e criar requisições

## Funcionalidades

### Para Todos os Usuários com Permissão
- Visualizar materiais em estoque
- Criar requisições de materiais
- Visualizar histórico de requisições
- Participar de conversas com administradores
- Agendar entregas
- Confirmar recebimento de materiais

### Para Administradores e Almoxarifes
- Adicionar e editar materiais
- Aprovar ou rejeitar requisições
- Editar quantidades solicitadas durante a aprovação
- Visualizar todas as requisições
- Concluir processos após entrega
- Participar de conversas com usuários

### Interface Organizada (Estilo Gmail)

### Interface do Administrador
- Lista de requisições em formato de caixa de entrada
- Visualização resumida com número da requisição, requisitante, data, status e última mensagem
- Clique em qualquer requisição para expandir detalhes e ações
- Interface limpa e organizada semelhante ao Gmail
- Uma única entrada por requisição, mostrando o status atual

### Interface do Usuário
- Lista de notificações em formato de caixa de entrada
- Visualização resumida com número da requisição, data, status e última mensagem
- Clique em qualquer notificação para expandir detalhes e ações
- Ações rápidas diretamente na lista (agendar, responder, confirmar recebimento)
- **Agrupamento de notificações**: Uma única entrada por requisição, mostrando a notificação mais recente

## Arquivos Principais

### Diretório Principal
- `almoxarifado.php`: Redireciona para o diretório do almoxarifado

### Diretório almoxarifado/
- `index.php`: Página principal do almoxarifado
- `requisicao.php`: Formulário para criar requisições
- `admin_notificacoes.php`: Interface de administração de requisições (estilo Gmail)
- `notificacoes.php`: Interface de notificações para usuários (estilo Gmail)
- `material_add.php`: Formulário para adicionar/editar materiais

## Como Usar

### 1. **Acesso**
O módulo de almoxarifado aparece no menu principal para usuários com perfil de Administrador, Almoxarife, Visualizador ou Gestor.

### 2. **Gerenciamento de Materiais**
- Acesse "Almoxarifado" no menu
- Clique em "Adicionar Material" para cadastrar novos materiais (somente administradores)
- Durante o cadastro, informe a "Quantidade Máxima por Requisição" para o item
- Use a pesquisa para encontrar materiais específicos

### 3. **Criação de Requisições**
- Na página principal do almoxarifado, clique em "Nova Requisição"
- Selecione o local de destino
- Adicione os materiais desejados e suas quantidades
- O sistema informará a quantidade máxima permitida para cada item
- O campo "Justificativa" só será obrigatório se algum item solicitado ultrapassar a quantidade máxima permitida
- Envie a requisição

### 4. **Interface de Notificações (Estilo Gmail)**
- **Para Administradores**: Acesse "Gerenciar Requisições" para ver a lista de requisições
- **Para Usuários**: Acesse "Minhas Notificações" para ver a lista de notificações
- Visualize informações resumidas diretamente na lista
- Clique em qualquer item para expandir detalhes e ações

### 5. **Processo de Aprovação** (para administradores e almoxarifes)
- Na interface estilo Gmail, clique em uma requisição para expandir seus detalhes
- Edite as quantidades solicitadas se necessário
- Aprove a requisição (isso deduzirá automaticamente os itens do estoque)
- Ou rejeite a requisição com uma justificativa

### 6. **Agendamento e Entrega** (para usuários)
- Na interface estilo Gmail, clique em uma notificação para expandir detalhes
- Use os botões de ação rápida para agendar entrega ou responder
- Ou confirme o recebimento diretamente se já recebeu os produtos

### 7. **Conclusão do Processo** (para administradores)
- Após o usuário confirmar o recebimento, o administrador pode concluir o processo
- Isso fecha a comunicação e marca o processo como finalizado

### 8. **Histórico de Conversas**
- Toda a comunicação entre usuários e administradores é registrada
- Cada alteração de quantidade é registrada na conversa
- Cada ação importante é registrada (aprovação, rejeição, agendamento, etc.)
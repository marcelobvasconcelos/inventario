# Módulo de Almoxarifado

## Visão Geral
O módulo de almoxarifado é uma extensão do sistema de inventário que permite gerenciar produtos, requisições e estoque de materiais.

## Estrutura de Tabelas

### almoxarifado_produtos
- `id`: Identificador único do produto
- `nome`: Nome do produto
- `descricao`: Descrição detalhada do produto
- `unidade_medida`: Unidade de medida (ex: kg, litros, unidades)
- `estoque_atual`: Quantidade atual em estoque
- `estoque_minimo`: Quantidade mínima recomendada em estoque
- `quantidade_maxima_requisicao`: Quantidade máxima permitida por requisição (sem justificativa)

### almoxarifado_requisicoes
- `id`: Identificador único da requisição
- `usuario_id`: ID do usuário que fez a requisição
- `local_id`: ID do local de destino (opcional)
- `data_requisicao`: Data e hora da requisição
- `status`: Status da requisição (pendente, aprovada, rejeitada, concluida)
- `justificativa`: Justificativa da requisição (obrigatória apenas se algum item ultrapassar a quantidade máxima)

### almoxarifado_requisicoes_itens
- `id`: Identificador único do item da requisição
- `requisicao_id`: ID da requisição
- `produto_id`: ID do produto solicitado
- `quantidade_solicitada`: Quantidade solicitada
- `quantidade_entregue`: Quantidade entregue (não utilizada atualmente)
- `observacao`: Observações sobre o item (não utilizada atualmente)

## Perfis de Usuário
- **Administrador**: Acesso completo a todas as funcionalidades
- **Almoxarife**: Acesso às funcionalidades de almoxarifado
- **Visualizador**: Acesso para visualizar produtos e criar requisições
- **Gestor**: Acesso para visualizar produtos e criar requisições

## Funcionalidades

### Para Todos os Usuários com Permissão
- Visualizar produtos em estoque
- Criar requisições de produtos

### Para Administradores e Almoxarifes
- Adicionar e editar produtos
- Aprovar ou rejeitar requisições
- Visualizar todas as requisições

## API Endpoints

### Processamento de Requisições
- `api/almoxarifado_processar_requisicao.php`: Processa uma nova requisição
- `api/almoxarifado_aprovar_requisicao.php`: Aprova uma requisição pendente
- `api/almoxarifado_rejeitar_requisicao.php`: Rejeita uma requisição pendente
- `api/almoxarifado_confirmar_recebimento.php`: Confirma o recebimento de uma requisição aprovada

### Listagem de Requisições
- `api/almoxarifado_listar_requisicoes.php`: Lista todas as requisições (para administradores)
- `api/almoxarifado_listar_minhas_requisicoes.php`: Lista as requisições do usuário logado

## Arquivos Principais

### Diretório Principal
- `almoxarifado.php`: Redireciona para o diretório do almoxarifado

### Diretório almoxarifado/
- `index.php`: Página principal do almoxarifado
- `add_produto.php`: Formulário para adicionar/editar produtos
- `requisicao.php`: Formulário para criar requisições

## Como Usar

1. **Acesso**: O módulo de almoxarifado aparece no menu principal para usuários com perfil de Administrador, Almoxarife, Visualizador ou Gestor.

2. **Gerenciamento de Produtos**:
   - Acesse "Almoxarifado" no menu
   - Clique em "Adicionar Produto" para cadastrar novos produtos
   - Durante o cadastro, informe a "Quantidade Máxima por Requisição" para o item
   - Use a pesquisa para encontrar produtos específicos

3. **Criação de Requisições**:
   - Na página principal do almoxarifado, clique em "Nova Requisição"
   - Selecione o local de destino
   - Adicione os produtos desejados e suas quantidades
   - O sistema informará a quantidade máxima permitida para cada item
   - O campo "Justificativa" só será obrigatório se algum item solicitado ultrapassar a quantidade máxima permitida
   - Envie a requisição

4. **Aprovação de Requisições** (para administradores e almoxarifes):
   - Acesse "Gerenciar Notificações" no menu
   - Clique na aba "Almoxarifado"
   - Visualize as requisições pendentes
   - Aprove ou rejeite as requisições conforme necessário

5. **Confirmação de Recebimento** (para usuários):
   - Acesse "Minhas Notificações" no menu
   - Clique na aba "Almoxarifado"
   - Visualize suas requisições
   - Confirme o recebimento das requisições aprovadas
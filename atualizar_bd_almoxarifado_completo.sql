-- atualizar_bd_almoxarifado_completo.sql - Script completo para criar todas as tabelas do módulo de almoxarifado

-- Tabela almoxarifado_produtos
CREATE TABLE IF NOT EXISTS almoxarifado_produtos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    unidade_medida VARCHAR(50),
    estoque_atual INT DEFAULT 0,
    estoque_minimo INT DEFAULT 0
);

-- Tabela almoxarifado_requisicoes
CREATE TABLE IF NOT EXISTS almoxarifado_requisicoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    local_id INT,
    data_requisicao DATETIME NOT NULL,
    status ENUM('pendente', 'aprovada', 'rejeitada', 'concluida') DEFAULT 'pendente',
    justificativa TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (local_id) REFERENCES locais(id)
);

-- Tabela almoxarifado_requisicoes_itens
CREATE TABLE IF NOT EXISTS almoxarifado_requisicoes_itens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requisicao_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade_solicitada INT NOT NULL,
    quantidade_entregue INT DEFAULT 0,
    observacao TEXT,
    FOREIGN KEY (requisicao_id) REFERENCES almoxarifado_requisicoes(id),
    FOREIGN KEY (produto_id) REFERENCES almoxarifado_produtos(id)
);
<?php
// atualizar_bd_almoxarifado.php - Script para atualizar o banco de dados com as tabelas do almoxarifado
require_once 'config/db.php';

try {
    // Verificar se as tabelas já existem
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'almoxarifado_produtos'");
    $stmt->execute();
    $tabela_produtos = $stmt->fetch();
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'almoxarifado_requisicoes'");
    $stmt->execute();
    $tabela_requisicoes = $stmt->fetch();
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'almoxarifado_requisicoes_itens'");
    $stmt->execute();
    $tabela_itens = $stmt->fetch();
    
    if (!$tabela_produtos || !$tabela_requisicoes || !$tabela_itens) {
        // Criar as tabelas
        $sql = "
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
        ";
        
        $pdo->exec($sql);
        echo "Tabelas do almoxarifado criadas com sucesso!\n";
    } else {
        echo "As tabelas do almoxarifado já existem no banco de dados.\n";
    }
    
    // Verificar se o perfil 'Almoxarife' já existe
    $stmt = $pdo->prepare("SELECT id FROM perfis WHERE nome = ?");
    $stmt->execute(['Almoxarife']);
    $perfil = $stmt->fetch();
    
    if (!$perfil) {
        // Inserir o novo perfil
        $stmt = $pdo->prepare("INSERT INTO perfis (nome) VALUES (?)");
        $stmt->execute(['Almoxarife']);
        echo "Perfil 'Almoxarife' adicionado com sucesso!\n";
    } else {
        echo "Perfil 'Almoxarife' já existe no banco de dados.\n";
    }
} catch (Exception $e) {
    echo "Erro ao atualizar banco de dados: " . $e->getMessage() . "\n";
}
?>
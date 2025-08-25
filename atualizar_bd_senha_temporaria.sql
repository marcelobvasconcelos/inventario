-- Adiciona coluna para identificar se o usuário está usando uma senha temporária
ALTER TABLE usuarios ADD COLUMN senha_temporaria BOOLEAN DEFAULT FALSE;

-- Cria tabela para armazenar solicitações de recuperação de senha
CREATE TABLE IF NOT EXISTS solicitacoes_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'processada', 'cancelada') DEFAULT 'pendente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
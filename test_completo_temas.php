<?php
// Inicia a sessão PHP se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redireciona para a página de login se o usuário não estiver logado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Inclui a conexão com o banco de dados para buscar notificações
require_once __DIR__ . '/../config/db.php';

// Busca o tema preferido do usuário
$tema_usuario = 'padrao';
if (isset($_SESSION['id'])) {
    try {
        $stmt_tema = $pdo->prepare("SELECT tema_preferido FROM usuarios WHERE id = ?");
        $stmt_tema->execute([$_SESSION['id']]);
        $tema_result = $stmt_tema->fetchColumn();
        
        // Se houver um tema definido, usa ele, senão usa o padrão
        if ($tema_result) {
            $tema_usuario = $tema_result;
        }
    } catch (Exception $e) {
        // Em caso de erro, usa o tema padrão
        $tema_usuario = 'padrao';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste Completo de Temas</title>
    <!-- Carregando style.css primeiro -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Carregando tema específico -->
    <link rel="stylesheet" id="tema-css" href="css/tema_<?php echo htmlspecialchars($tema_usuario); ?>.css">
    <!-- Carregando CSS para temas -->
    <link rel="stylesheet" href="css/temas.css">
</head>
<body>
    <header class="main-header">
        <h1>Teste Completo de Temas</h1>
        <nav>
            <a href="#" onclick="mudarTema('padrao')">Padrão</a>
            <a href="#" onclick="mudarTema('azul')">Azul</a>
            <a href="#" onclick="mudarTema('verde')">Verde</a>
            <a href="#" onclick="mudarTema('roxo')">Roxo</a>
        </nav>
    </header>
    
    <main style="padding: 20px;">
        <h2>Teste de aplicação de temas</h2>
        <p>Tema atual: <strong><?php echo htmlspecialchars($tema_usuario); ?></strong></p>
        
        <div style="margin: 20px 0;">
            <h3>Elementos para teste:</h3>
            <button class="btn btn-primary">Botão Primário</button>
            <button class="btn btn-success">Botão Secundário</button>
            <button class="btn btn-danger">Botão de Perigo</button>
            <button class="btn btn-warning">Botão de Aviso</button>
        </div>
        
        <div style="margin: 20px 0;">
            <h3>Cores do tema atual:</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <div style="width: 80px; height: 80px; background: var(--cor-primaria, #000); border: 1px solid #000;"></div>
                <div style="width: 80px; height: 80px; background: var(--cor-primaria-escura, #000); border: 1px solid #000;"></div>
                <div style="width: 80px; height: 80px; background: var(--cor-secundaria, #000); border: 1px solid #000;"></div>
                <div style="width: 80px; height: 80px; background: var(--cor-secundaria-escura, #000); border: 1px solid #000;"></div>
                <div style="width: 80px; height: 80px; background: var(--cor-destaque, #000); border: 1px solid #000;"></div>
                <div style="width: 80px; height: 80px; background: var(--cor-perigo, #000); border: 1px solid #000;"></div>
            </div>
        </div>
        
        <div style="margin: 20px 0;">
            <h3>Informações de depuração:</h3>
            <p>URL do CSS do tema: <span id="css-url"></span></p>
            <p>Status: <span id="status">Aguardando ação...</span></p>
        </div>
    </main>

    <script>
        // Atualiza a URL exibida quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            atualizarInfoCSS();
        });
        
        function mudarTema(tema) {
            console.log('Mudando tema para:', tema);
            atualizarStatus('Mudando tema...');
            
            // Atualiza o CSS do tema
            const temaLink = document.getElementById('tema-css');
            if (temaLink) {
                // Constrói o novo href com um timestamp para evitar cache
                const timestamp = new Date().getTime();
                const novoHref = `css/tema_${tema}.css?v=${timestamp}`;
                console.log('Novo href:', novoHref);
                
                temaLink.href = novoHref;
                
                atualizarInfoCSS();
                atualizarStatus('Tema atualizado');
                
                // Força reflow após um pequeno atraso
                setTimeout(forcarReflow, 100);
            }
            
            // Atualiza o texto do tema atual na página
            const temaAtual = document.querySelector('main h2 + p strong');
            if (temaAtual) {
                temaAtual.textContent = tema;
            }
        }
        
        // Função para atualizar informações do CSS
        function atualizarInfoCSS() {
            const temaLink = document.getElementById('tema-css');
            if (temaLink) {
                document.getElementById('css-url').textContent = temaLink.href;
            }
        }
        
        // Função para atualizar status
        function atualizarStatus(mensagem) {
            document.getElementById('status').textContent = mensagem;
        }
        
        // Função para forçar reflow
        function forcarReflow() {
            document.body.style.display = 'none';
            document.body.offsetHeight; // Trigger reflow
            document.body.style.display = 'block';
        }
    </script>
</body>
</html>
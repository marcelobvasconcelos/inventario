<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste de Temas</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Inclui o CSS do tema padrão -->
    <link rel="stylesheet" id="tema-css" href="css/tema_padrao.css">
    <!-- Inclui o CSS para os temas -->
    <link rel="stylesheet" href="css/temas.css">
    <!-- Inclui o CSS de teste -->
    <link rel="stylesheet" href="css/teste_temas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <h1>Teste de Temas</h1>
        <nav>
            <a href="/inventario/index.php">Voltar</a>
        </nav>
        <div class="user-menu">
            <div class="user-menu-dropdown">
                <button class="user-menu-button">Menu de Usuário <i class="fas fa-caret-down"></i></button>
                <div class="user-menu-content">
                    <!-- Link para selecionar tema -->
                    <a href="#" id="seletor-tema">Selecionar Tema</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Modal para seleção de tema -->
    <div id="modal-tema" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Selecionar Tema</h2>
            <div class="temas-container">
                <div class="tema-opcao" data-tema="padrao">
                    <div class="tema-preview padrao"></div>
                    <span>Padrão</span>
                </div>
                <div class="tema-opcao" data-tema="azul">
                    <div class="tema-preview azul"></div>
                    <span>Azul</span>
                </div>
                <div class="tema-opcao" data-tema="verde">
                    <div class="tema-preview verde"></div>
                    <span>Verde</span>
                </div>
                <div class="tema-opcao" data-tema="roxo">
                    <div class="tema-preview roxo"></div>
                    <span>Roxo</span>
                </div>
            </div>
        </div>
    </div>
    
    <main>
        <h2>Teste de Aplicação de Temas</h2>
        <p>Tema atual: <strong id="tema-atual">padrao</strong></p>
        
        <div class="teste-tema teste-tema-padrao" id="elemento-teste">
            <h3>Elemento de Teste de Tema</h3>
            <p>Este elemento deve ter borda e fundo coloridos de acordo com o tema selecionado.</p>
            <button class="btn btn-primary">Botão Primário</button>
            <button class="btn btn-success">Botão Secundário</button>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>Elementos do Sistema</h3>
            <button class="btn btn-primary">Botão Primário</button>
            <button class="btn btn-success">Botão Secundário</button>
            <button class="btn btn-danger">Botão de Perigo</button>
            <button class="btn btn-warning">Botão de Aviso</button>
            <button class="btn btn-info">Botão de Informação</button>
            <button class="btn btn-custom">Botão Customizado</button>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>Header do Sistema</h3>
            <p>O header acima deve mudar de cor de acordo com o tema selecionado.</p>
        </div>
    </main>
    
    <!-- Inclui o JavaScript simplificado para os temas -->
    <script src="js/temas_simples.js"></script>
</body>
</html>
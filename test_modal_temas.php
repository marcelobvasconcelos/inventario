<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste de Modal de Temas</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/temas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div style="padding: 20px;">
        <h1>Teste de Modal de Temas</h1>
        <p>Clique no botão abaixo para abrir o modal de seleção de temas.</p>
        <button id="seletor-tema" class="btn-custom">Selecionar Tema</button>
    </div>
    
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
    
    <script>
        // Elementos do modal
        const modal = document.getElementById('modal-tema');
        const seletorTema = document.getElementById('seletor-tema');
        const closeBtn = modal.querySelector('.close-button');
        const temaOpcoes = document.querySelectorAll('.tema-opcao');
        
        console.log('Modal:', modal);
        console.log('Seletor Tema:', seletorTema);
        console.log('Close Button:', closeBtn);
        console.log('Tema Opções:', temaOpcoes);
        
        // Verifica se todos os elementos necessários existem
        if (!modal || !seletorTema || !closeBtn || !temaOpcoes) {
            console.error('Erro: Alguns elementos necessários para o seletor de temas não foram encontrados.');
        } else {
            console.log('Todos os elementos encontrados com sucesso.');
            
            // Abre o modal quando o link "Selecionar Tema" é clicado
            seletorTema.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Abrindo modal...');
                modal.style.display = 'flex';
            });
            
            // Fecha o modal quando o botão de fechar é clicado
            closeBtn.addEventListener('click', function() {
                console.log('Fechando modal...');
                modal.style.display = 'none';
            });
            
            // Fecha o modal quando o usuário clica fora do conteúdo
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    console.log('Fechando modal ao clicar fora...');
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
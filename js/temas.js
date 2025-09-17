// temas.js - JavaScript para gerenciar a seleção de temas

// Variável para controlar se o seletor de temas já foi inicializado
let temasInicializados = false;

// Função para inicializar o seletor de temas
function inicializarSeletorTemas() {
    // Verifica se o seletor já foi inicializado
    if (temasInicializados) {
        console.log('Seletor de temas já inicializado, ignorando...');
        return false;
    }
    
    console.log('Inicializando seletor de temas...');
    
    // Elementos do modal
    const modal = document.getElementById('modal-tema');
    const seletorTema = document.getElementById('seletor-tema');
    const closeBtn = modal ? modal.querySelector('.close-button') : null;
    const temaOpcoes = document.querySelectorAll('.tema-opcao');
    
    // Verifica se todos os elementos necessários existem
    if (!modal || !seletorTema || !closeBtn || temaOpcoes.length === 0) {
        console.error('Erro: Alguns elementos necessários para o seletor de temas não foram encontrados.');
        console.log('Modal:', modal);
        console.log('Seletor Tema:', seletorTema);
        console.log('Close Button:', closeBtn);
        console.log('Tema Opções:', temaOpcoes);
        return false;
    }
    
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
    
    // Adiciona evento de clique para cada opção de tema
    temaOpcoes.forEach(function(opcao) {
        opcao.addEventListener('click', function() {
            const tema = this.getAttribute('data-tema');
            console.log('Tema selecionado:', tema);
            aplicarTema(tema);
        });
    });
    
    // Marca como inicializado
    temasInicializados = true;
    console.log('Seletor de temas inicializado com sucesso.');
    return true;
}

// Função para aplicar o tema selecionado
function aplicarTema(tema) {
    console.log('Aplicando tema:', tema);
    
    // Fecha o modal
    const modal = document.getElementById('modal-tema');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Salva o tema no servidor
    fetch('/inventario/tema_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tema=' + encodeURIComponent(tema)
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('Erro na resposta do servidor: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos do servidor:', data);
        if (data.success) {
            // Atualiza o CSS do tema
            atualizarCSSTema(tema);
            
            // Atualiza o href do link CSS do tema
            atualizarLinkCSSTema(tema);
            
            // Atualiza a sessão do usuário com o novo tema
            atualizarSessaoTema(tema);
            
            // Mostra uma mensagem de sucesso
            mostrarMensagem('Tema atualizado com sucesso!', 'success');
        } else {
            mostrarMensagem('Erro ao atualizar tema: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar tema:', error);
        mostrarMensagem('Erro ao atualizar tema: ' + error.message, 'error');
    });
}

// Função para atualizar o link CSS do tema
function atualizarLinkCSSTema(tema) {
    console.log('Atualizando link CSS do tema para:', tema);
    const temaLink = document.getElementById('tema-css');
    if (temaLink) {
        // Determina o caminho base com base na URL atual
        const isAlmoxarifado = window.location.pathname.includes('/almoxarifado/');
        const cssPath = isAlmoxarifado ? '../' : '';
        
        // Constrói o novo href com um timestamp para evitar cache
        const timestamp = new Date().getTime();
        const novoHref = `${cssPath}css/tema_${tema}.css?v=${timestamp}`;
        console.log('Novo href:', novoHref);
        
        // Remove o link existente e cria um novo para forçar o recarregamento
        const oldLink = temaLink;
        const newLink = document.createElement('link');
        newLink.id = 'tema-css';
        newLink.rel = 'stylesheet';
        newLink.type = 'text/css';
        newLink.href = novoHref;
        
        // Adiciona evento de carregamento
        newLink.onload = function() {
            console.log('CSS do tema carregado com sucesso');
            // Força a atualização visual da página
            forcarAtualizacaoVisual();
        };
        newLink.onerror = function() {
            console.error('Erro ao carregar o CSS do tema');
        };
        
        // Substitui o link antigo pelo novo
        oldLink.parentNode.replaceChild(newLink, oldLink);
    } else {
        console.error('Elemento #tema-css não encontrado');
    }
}

// Função para atualizar o CSS do tema (compatibilidade com navegadores antigos)
function atualizarCSSTema(tema) {
    console.log('Atualizando variáveis CSS do tema para:', tema);
    
    // Atualiza todas as variáveis CSS diretamente no elemento :root
    const root = document.documentElement;
    
    // Define as variáveis CSS com base no tema selecionado
    let corPrimaria, corPrimariaEscura, corSecundaria, corSecundariaEscura;
    
    switch(tema) {
        case 'azul':
            corPrimaria = '#2980b9';
            corPrimariaEscura = '#1c5a8a';
            corSecundaria = '#3498db';
            corSecundariaEscura = '#217dbb';
            break;
        case 'verde':
            corPrimaria = '#27ae60';
            corPrimariaEscura = '#219653';
            corSecundaria = '#2ecc71';
            corSecundariaEscura = '#25a25a';
            break;
        case 'roxo':
            corPrimaria = '#8e44ad';
            corPrimariaEscura = '#6c3483';
            corSecundaria = '#9b59b6';
            corSecundariaEscura = '#7d3c98';
            break;
        case 'altocontraste':
            corPrimaria = '#000000';
            corPrimariaEscura = '#000000';
            corSecundaria = '#ffff00';
            corSecundariaEscura = '#ffff00';
            break;
        case 'padrao':
        default:
            corPrimaria = '#124a80';
            corPrimariaEscura = '#0e3a66';
            corSecundaria = '#28a745';
            corSecundariaEscura = '#218838';
            break;
    }
    
    // Aplica as variáveis CSS
    root.style.setProperty('--cor-primaria', corPrimaria);
    root.style.setProperty('--cor-primaria-escura', corPrimariaEscura);
    root.style.setProperty('--cor-secundaria', corSecundaria);
    root.style.setProperty('--cor-secundaria-escura', corSecundariaEscura);
    
    console.log('Variáveis CSS atualizadas');
    
    // Força a atualização visual da página
    forcarAtualizacaoVisual();
}

// Função para atualizar a sessão do usuário com o novo tema
function atualizarSessaoTema(tema) {
    console.log('Atualizando sessão com tema:', tema);
    
    // Envia uma requisição para atualizar a sessão do usuário
    fetch('/inventario/tema_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tema=' + encodeURIComponent(tema) + '&atualizar_sessao=1'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro ao atualizar sessão');
        }
        return response.json();
    })
    .then(data => {
        console.log('Sessão atualizada:', data);
    })
    .catch(error => {
        console.error('Erro ao atualizar sessão:', error);
    });
}

// Função para forçar a atualização visual da página
function forcarAtualizacaoVisual() {
    console.log('Forçando atualização visual...');
    
    // Força um reflow/repaint
    document.body.style.display = 'none';
    document.body.offsetHeight; // Trigger reflow
    document.body.style.display = 'block';
    
    // Outra abordagem para forçar reflow
    const elemento = document.body;
    const display = elemento.style.display;
    elemento.style.display = 'none';
    elemento.offsetHeight; // Trigger reflow
    elemento.style.display = display;
    
    console.log('Atualização visual forçada');
}

// Função para mostrar mensagens de feedback
function mostrarMensagem(mensagem, tipo) {
    console.log('Mostrando mensagem:', mensagem, tipo);
    
    // Remove mensagens anteriores
    const mensagensAnteriores = document.querySelectorAll('.mensagem-tema');
    mensagensAnteriores.forEach(msg => msg.remove());
    
    // Cria novo elemento de mensagem
    const mensagemEl = document.createElement('div');
    mensagemEl.className = 'mensagem-tema ' + tipo;
    mensagemEl.textContent = mensagem;
    
    // Adiciona estilo básico
    mensagemEl.style.position = 'fixed';
    mensagemEl.style.top = '20px';
    mensagemEl.style.right = '20px';
    mensagemEl.style.padding = '15px 20px';
    mensagemEl.style.borderRadius = '5px';
    mensagemEl.style.color = 'white';
    mensagemEl.style.fontWeight = 'bold';
    mensagemEl.style.zIndex = '10000';
    mensagemEl.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
    mensagemEl.style.maxWidth = '300px';
    
    // Define cores com base no tipo
    if (tipo === 'success') {
        mensagemEl.style.backgroundColor = '#28a745';
    } else {
        mensagemEl.style.backgroundColor = '#e74c3c';
    }
    
    // Adiciona ao body
    document.body.appendChild(mensagemEl);
    
    // Remove a mensagem após 3 segundos
    setTimeout(() => {
        if (mensagemEl.parentNode) {
            mensagemEl.parentNode.removeChild(mensagemEl);
        }
    }, 3000);
}

// Inicializa o seletor de temas quando o DOM estiver pronto
if (document.readyState === 'loading') {
    // DOM ainda está carregando, espera o evento DOMContentLoaded
    document.addEventListener('DOMContentLoaded', inicializarSeletorTemas);
} else {
    // DOM já está carregado, inicializa imediatamente
    inicializarSeletorTemas();
}

// Também tenta inicializar após um pequeno atraso para garantir que todos os elementos estejam prontos
setTimeout(inicializarSeletorTemas, 1000);
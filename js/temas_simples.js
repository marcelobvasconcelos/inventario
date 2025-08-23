// temas_simples.js - Versão simplificada do JavaScript para testar temas

console.log('Script de temas carregado');

// Função para aplicar o tema selecionado
function aplicarTema(tema) {
    console.log('Aplicando tema:', tema);
    
    // Fecha o modal
    const modal = document.getElementById('modal-tema');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Atualiza o CSS do tema
    atualizarCSSTema(tema);
    
    // Atualiza o href do link CSS do tema
    atualizarLinkCSSTema(tema);
    
    // Atualiza o nome do tema na página
    const temaAtual = document.getElementById('tema-atual');
    if (temaAtual) {
        temaAtual.textContent = tema;
    }
    
    // Atualiza a classe do elemento de teste
    const elementoTeste = document.getElementById('elemento-teste');
    if (elementoTeste) {
        // Remove classes antigas
        elementoTeste.className = elementoTeste.className.replace(/teste-tema-\w+/g, '');
        // Adiciona a nova classe
        elementoTeste.classList.add('teste-tema-' + tema);
    }
    
    // Mostra uma mensagem de sucesso
    mostrarMensagem('Tema atualizado com sucesso!');
}

// Função para atualizar o link CSS do tema
function atualizarLinkCSSTema(tema) {
    console.log('Atualizando link CSS do tema para:', tema);
    const temaLink = document.getElementById('tema-css');
    if (temaLink) {
        // Constrói o novo href com um timestamp para evitar cache
        const timestamp = new Date().getTime();
        const novoHref = `css/tema_${tema}.css?v=${timestamp}`;
        console.log('Novo href:', novoHref);
        
        temaLink.href = novoHref;
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
}

// Função para mostrar mensagens de feedback
function mostrarMensagem(mensagem) {
    console.log('Mostrando mensagem:', mensagem);
    
    // Remove mensagens anteriores
    const mensagensAnteriores = document.querySelectorAll('.mensagem-tema');
    mensagensAnteriores.forEach(msg => msg.remove());
    
    // Cria novo elemento de mensagem
    const mensagemEl = document.createElement('div');
    mensagemEl.className = 'mensagem-tema';
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
    mensagemEl.style.backgroundColor = '#28a745';
    
    // Adiciona ao body
    document.body.appendChild(mensagemEl);
    
    // Remove a mensagem após 3 segundos
    setTimeout(() => {
        if (mensagemEl.parentNode) {
            mensagemEl.parentNode.removeChild(mensagemEl);
        }
    }, 3000);
}

// Adiciona eventos quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, configurando eventos...');
    
    // Configura o botão de seleção de tema
    const seletorTema = document.getElementById('seletor-tema');
    if (seletorTema) {
        seletorTema.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Abrindo modal...');
            const modal = document.getElementById('modal-tema');
            if (modal) {
                modal.style.display = 'flex';
            }
        });
    }
    
    // Configura o botão de fechar do modal
    const closeBtn = document.querySelector('#modal-tema .close-button');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            console.log('Fechando modal...');
            const modal = document.getElementById('modal-tema');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Configura o fechamento do modal ao clicar fora
    const modal = document.getElementById('modal-tema');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                console.log('Fechando modal ao clicar fora...');
                modal.style.display = 'none';
            }
        });
    }
    
    // Configura as opções de tema
    const temaOpcoes = document.querySelectorAll('.tema-opcao');
    temaOpcoes.forEach(function(opcao) {
        opcao.addEventListener('click', function() {
            const tema = this.getAttribute('data-tema');
            console.log('Tema selecionado:', tema);
            aplicarTema(tema);
        });
    });
    
    console.log('Eventos configurados com sucesso.');
});
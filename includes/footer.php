    </main>
    
    <script>
    // Função para atualizar o badge do sino de notificações
    function atualizarBadgeNotificacoes() {
        fetch('/inventario/api/get_notificacoes_pendentes.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na rede ou no servidor.');
                }
                return response.json();
            })
            .then(data => {
                const badge = document.querySelector('.notification-bell .notification-badge');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count;
                    } else {
                        const bell = document.querySelector('.notification-bell');
                        if (bell) {
                            const span = document.createElement('span');
                            span.className = 'notification-badge';
                            span.textContent = data.count;
                            bell.appendChild(span);
                        }
                    }
                } else {
                    if (badge) badge.remove();
                }
            })
            .catch(error => {
                console.error('Erro ao buscar notificações:', error);
            });
    }

    // Atualiza a cada 30 segundos
    setInterval(atualizarBadgeNotificacoes, 30000);
    // Atualiza ao carregar a página
    document.addEventListener('DOMContentLoaded', atualizarBadgeNotificacoes);

    // Expõe a função para ser chamada em outras partes do código
    window.atualizarBadgeNotificacoes = atualizarBadgeNotificacoes;
    
    // Padronização de comportamento dos botões btn-custom
    document.addEventListener('DOMContentLoaded', function() {
        const customButtons = document.querySelectorAll('.btn-custom');
        customButtons.forEach(function(button) {
            // Garante que botões <a> também tenham o comportamento de botão
            if (button.tagName === 'A') {
                button.addEventListener('click', function(e) {
                    // Adiciona efeito visual de clique
                    this.classList.add('btn-clicked');
                    setTimeout(() => {
                        this.classList.remove('btn-clicked');
                    }, 200);
                });
            }
        });
    });
    </script>
    
    <!-- Inclui o JavaScript do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sistema de Inventário</p>
        <p>Sistema desenvolvido pela <a href="https://uast.ufrpe.br/sti" target="_blank" style="color: white;"><strong>Seção de Tecnologia da Informação (STI-UAST)</strong></a></p>
    </footer>
</body>
</html>
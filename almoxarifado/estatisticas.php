<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';
require_once 'config.php';

// --- Controle de Acesso ---
$allowed_roles = ['Administrador', 'Almoxarife', 'Visualizador', 'Gestor'];
if (!isset($_SESSION['permissao']) || !in_array($_SESSION['permissao'], $allowed_roles)) {
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once $base_path . '/includes/footer.php';
    exit;
}

// Define se o usuário tem visão privilegiada
$is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);
?>

<div class="page-header-sticky">
    <div class="almoxarifado-header">
        <h2>Estatísticas e Gráficos do Almoxarifado</h2>
        <?php require_once 'menu_almoxarifado.php'; ?>
    </div>
</div>

<div class="estatisticas-container">
    <!-- Interface de Parâmetros -->
    <div class="parametros-panel">
        <h3>Configurar Gráfico</h3>

        <div class="parametro-group">
            <label for="chart_type">Tipo de Gráfico:</label>
            <select id="chart_type" class="form-control">
                <option value="bar">Gráfico de Barras</option>
                <option value="line">Gráfico de Linhas</option>
                <option value="pie">Gráfico de Pizza</option>
            </select>
        </div>

        <div class="parametro-group">
            <label for="period">Período de Análise:</label>
            <select id="period" class="form-control">
                <option value="7days">Últimos 7 dias</option>
                <option value="30days">Últimos 30 dias</option>
                <option value="quarter">Último trimestre</option>
                <option value="year">Último ano</option>
                <option value="custom">Intervalo personalizado</option>
            </select>
        </div>

        <div id="custom_dates" class="parametro-group" style="display: none;">
            <label>Data Inicial:</label>
            <input type="date" id="start_date" class="form-control">
            <label>Data Final:</label>
            <input type="date" id="end_date" class="form-control">
        </div>

        <div class="parametro-group">
            <label for="data_type">Tipo de Dados:</label>
            <select id="data_type" class="form-control">
                <option value="movimentacao">Movimentação de Itens (Entrada/Saída)</option>
                <option value="valor_estoque">Valor Total do Estoque</option>
                <option value="mais_requisitados">Itens Mais Requisitados</option>
                <option value="estoque_baixo">Itens Com Estoque Baixo</option>
                <option value="requisicoes_status">Requisições por Status</option>
                <option value="itens_especificos">Itens Específicos</option>
            </select>
        </div>

        <div id="specific_items" class="parametro-group" style="display: none;">
            <label for="item_search">Buscar Itens:</label>
            <input type="text" id="item_search" class="form-control" placeholder="Digite o nome ou código do item">
            <div id="item_suggestions" class="item-suggestions" style="display: none;"></div>
            <div id="selected_items" class="selected-items-list"></div>
        </div>

        <button id="generate_chart" class="btn-custom">Gerar Gráfico</button>
    </div>

    <!-- Área do Gráfico -->
    <div class="chart-container">
        <canvas id="chartCanvas"></canvas>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let chart = null;
const baseUrl = window.location.origin + '/inventario';

document.getElementById('period').addEventListener('change', function() {
    const customDates = document.getElementById('custom_dates');
    if (this.value === 'custom') {
        customDates.style.display = 'block';
    } else {
        customDates.style.display = 'none';
    }
});

document.getElementById('data_type').addEventListener('change', function() {
    const specificItems = document.getElementById('specific_items');
    if (this.value === 'itens_especificos') {
        specificItems.style.display = 'block';
    } else {
        specificItems.style.display = 'none';
    }
});

// Busca de itens específicos
let selectedItems = [];
document.getElementById('item_search').addEventListener('input', function() {
    const query = this.value;
    const suggestionsDiv = document.getElementById('item_suggestions');

    if (query.length < 2) {
        suggestionsDiv.style.display = 'none';
        return;
    }

    fetch(baseUrl + '/api/search_materiais.php?q=' + encodeURIComponent(query), { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.materiais.length > 0) {
                showItemSuggestions(data.materiais);
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => console.error('Erro:', error));
});

function showItemSuggestions(materiais) {
    const suggestionsDiv = document.getElementById('item_suggestions');
    suggestionsDiv.innerHTML = '';

    materiais.forEach(material => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'suggestion-item';
        itemDiv.textContent = `${material.nome} (${material.codigo})`;
        itemDiv.dataset.id = material.id;
        itemDiv.addEventListener('click', () => selectItem(material));
        suggestionsDiv.appendChild(itemDiv);
    });

    suggestionsDiv.style.display = 'block';
}

function selectItem(material) {
    if (!selectedItems.find(item => item.id === material.id)) {
        selectedItems.push(material);
        updateSelectedItemsDisplay();
    }

    document.getElementById('item_search').value = '';
    document.getElementById('item_suggestions').style.display = 'none';
}

function updateSelectedItemsDisplay() {
    const selectedDiv = document.getElementById('selected_items');
    selectedDiv.innerHTML = '';

    selectedItems.forEach(item => {
        const itemTag = document.createElement('div');
        itemTag.className = 'selected-item-tag';
        itemTag.innerHTML = `
            ${item.nome} (${item.codigo})
            <span class="remove-item" onclick="removeItem(${item.id})">&times;</span>
        `;
        selectedDiv.appendChild(itemTag);
    });
}

function removeItem(itemId) {
    selectedItems = selectedItems.filter(item => item.id !== itemId);
    updateSelectedItemsDisplay();
}

document.getElementById('generate_chart').addEventListener('click', function() {
    const params = {
        chart_type: document.getElementById('chart_type').value,
        period: document.getElementById('period').value,
        data_type: document.getElementById('data_type').value,
        start_date: document.getElementById('start_date').value,
        end_date: document.getElementById('end_date').value,
        items: selectedItems.map(item => item.id)
    };

    fetch(baseUrl + '/almoxarifado/api/get_chart_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(params),
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderChart(data.chartData, params.chart_type);
        } else {
            alert('Erro ao carregar dados: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao gerar gráfico');
    });
});

function renderChart(chartData, chartType) {
    const ctx = document.getElementById('chartCanvas').getContext('2d');

    if (chart) {
        chart.destroy();
    }

    chart = new Chart(ctx, {
        type: chartType,
        data: chartData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Estatísticas do Almoxarifado'
                }
            }
        }
    });
}
</script>

<style>
.estatisticas-container {
    display: flex;
    flex-wrap: wrap;
    padding: 20px 0;
}

.parametros-panel {
    flex: 1;
    min-width: 300px;
    margin: 10px;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.parametros-panel h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.parametro-group {
    margin-bottom: 15px;
}

.parametro-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.chart-container {
    flex: 2;
    min-width: 500px;
    margin: 10px;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#chartCanvas {
    max-width: 100%;
    height: 400px;
}

.selected-items-list {
    margin-top: 10px;
    max-height: 150px;
    overflow-y: auto;
}

.item-suggestions {
    border: 1px solid #ddd;
    max-height: 200px;
    overflow-y: auto;
    background: white;
    position: absolute;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.suggestion-item {
    padding: 8px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
}

.selected-item-tag {
    display: inline-block;
    background: #e9ecef;
    padding: 4px 8px;
    margin: 2px;
    border-radius: 4px;
    font-size: 14px;
}

.remove-item {
    margin-left: 8px;
    cursor: pointer;
    color: #dc3545;
    font-weight: bold;
}

.remove-item:hover {
    color: #c82333;
}

@media (max-width: 768px) {
    .estatisticas-container {
        flex-direction: column;
    }

    .parametros-panel,
    .chart-container {
        min-width: 100%;
    }
}
</style>

<?php
require_once $base_path . '/includes/footer.php';
?>
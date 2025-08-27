<?php
// Buscar todas as cidades ativas
try {
    $stmt = $pdo->prepare("SELECT * FROM cidades_senac_mg WHERE ativa = 1 ORDER BY nome_cidade ASC");
    $stmt->execute();
    $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cidades = [];
}

// Cidade selecionada
$cidade_selecionada = isset($_GET['cidade']) ? $_GET['cidade'] : '';
?>

<div class="filtro-cidade-container">
    <div class="filtro-header">
        <div class="filtro-icon">
            <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="filtro-info">
            <h3>Filtrar por Cidade</h3>
            <p>Encontre serviços na sua região</p>
        </div>
    </div>
    
    <div class="filtro-content">
        <!-- Busca Rápida -->
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="busca-cidade" placeholder="Digite o nome da cidade..." autocomplete="off">
            <div class="search-results" id="search-results"></div>
        </div>
        
        <!-- Filtro por Região -->
        <div class="regiao-filter">
            <label for="regiao-select">Filtrar por Região:</label>
            <select id="regiao-select" onchange="filtrarPorRegiao()">
                <option value="">Todas as Regiões</option>
                <option value="Metropolitana">Região Metropolitana</option>
                <option value="Sul de Minas">Sul de Minas</option>
                <option value="Triângulo Mineiro">Triângulo Mineiro</option>
                <option value="Zona da Mata">Zona da Mata</option>
                <option value="Norte de Minas">Norte de Minas</option>
                <option value="Centro-Oeste">Centro-Oeste</option>
                <option value="Vale do Rio Doce">Vale do Rio Doce</option>
                <option value="Vale do Aço">Vale do Aço</option>
                <option value="Campo das Vertentes">Campo das Vertentes</option>
                <option value="Central">Central</option>
                <option value="Alto Paranaíba">Alto Paranaíba</option>
                <option value="Jequitinhonha">Jequitinhonha</option>
                <option value="Vale do Mucuri">Vale do Mucuri</option>
                <option value="Noroeste">Noroeste</option>
            </select>
        </div>
        
        <!-- Lista de Cidades -->
        <div class="cidades-grid" id="cidades-grid">
            <div class="cidade-item <?= empty($cidade_selecionada) ? 'active' : '' ?>" onclick="selecionarCidade('')">
                <div class="cidade-icon">
                    <i class="fas fa-globe-americas"></i>
                </div>
                <div class="cidade-info">
                    <span class="cidade-nome">Todas as Cidades</span>
                    <span class="cidade-count">Ver todos</span>
                </div>
            </div>
            
            <?php foreach ($cidades as $cidade): ?>
                <div class="cidade-item <?= $cidade_selecionada == $cidade['id_cidade'] ? 'active' : '' ?>" 
                     data-regiao="<?= htmlspecialchars($cidade['regiao'] ?? '') ?>"
                     onclick="selecionarCidade('<?= $cidade['id_cidade'] ?>')">
                    <div class="cidade-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="cidade-info">
                        <span class="cidade-nome"><?= htmlspecialchars($cidade['nome_cidade']) ?></span>
                        <span class="cidade-uf">MG</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Cidades Populares -->
        <div class="cidades-populares">
            <h4><i class="fas fa-fire"></i> Cidades Populares</h4>
            <div class="populares-list">
                <button class="cidade-popular" onclick="selecionarCidade('7')">
                    <i class="fas fa-building"></i>
                    Belo Horizonte
                </button>
                <button class="cidade-popular" onclick="selecionarCidade('52')">
                    <i class="fas fa-graduation-cap"></i>
                    Juiz de Fora
                </button>
                <button class="cidade-popular" onclick="selecionarCidade('20')">
                    <i class="fas fa-industry"></i>
                    Contagem
                </button>
                <button class="cidade-popular" onclick="selecionarCidade('95')">
                    <i class="fas fa-leaf"></i>
                    Uberlândia
                </button>
                <button class="cidade-popular" onclick="selecionarCidade('8')">
                    <i class="fas fa-car"></i>
                    Betim
                </button>
                <button class="cidade-popular" onclick="selecionarCidade('77')">
                    <i class="fas fa-mountain"></i>
                    Montes Claros
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.filtro-cidade-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.filtro-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filtro-icon {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.filtro-info h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.filtro-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.filtro-content {
    padding: 1.5rem;
}

.search-box {
    position: relative;
    margin-bottom: 1.5rem;
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    z-index: 1;
}

.search-box input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.search-result-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background 0.3s ease;
}

.search-result-item:hover {
    background: #f8f9fa;
}

.search-result-item:last-child {
    border-bottom: none;
}

.regiao-filter {
    margin-bottom: 1.5rem;
}

.regiao-filter label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.regiao-filter select {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.regiao-filter select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.cidades-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 0.75rem;
    margin-bottom: 2rem;
    max-height: 400px;
    overflow-y: auto;
    padding-right: 10px;
}

.cidades-grid::-webkit-scrollbar {
    width: 6px;
}

.cidades-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.cidades-grid::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 3px;
}

.cidade-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.cidade-item:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
}

.cidade-item.active {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.cidade-icon {
    width: 35px;
    height: 35px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
    font-size: 1rem;
    flex-shrink: 0;
}

.cidade-item.active .cidade-icon {
    background: rgba(255,255,255,0.2);
    color: white;
}

.cidade-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.cidade-nome {
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cidade-uf, .cidade-count {
    font-size: 0.8rem;
    opacity: 0.7;
}

.cidades-populares {
    border-top: 2px solid #f8f9fa;
    padding-top: 1.5rem;
}

.cidades-populares h4 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cidades-populares h4 i {
    color: #ff6b6b;
}

.populares-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
}

.cidade-popular {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: #f8f9fa;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 500;
    color: #333;
}

.cidade-popular:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.cidade-popular i {
    font-size: 0.8rem;
    opacity: 0.8;
}

@media (max-width: 768px) {
    .cidades-grid {
        grid-template-columns: 1fr;
        max-height: 300px;
    }
    
    .populares-list {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filtro-content {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .populares-list {
        grid-template-columns: 1fr;
    }
    
    .filtro-header {
        padding: 1rem;
    }
    
    .filtro-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Dados das cidades para busca
const cidadesData = <?= json_encode($cidades) ?>;

// Busca em tempo real
document.getElementById('busca-cidade').addEventListener('input', function(e) {
    const termo = e.target.value.toLowerCase();
    const resultados = document.getElementById('search-results');
    
    if (termo.length < 2) {
        resultados.style.display = 'none';
        return;
    }
    
    const cidadesFiltradas = cidadesData.filter(cidade => 
        cidade.nome_cidade.toLowerCase().includes(termo)
    );
    
    if (cidadesFiltradas.length > 0) {
        resultados.innerHTML = cidadesFiltradas.map(cidade => 
            `<div class="search-result-item" onclick="selecionarCidade('${cidade.id_cidade}')">
                <i class="fas fa-map-marker-alt" style="margin-right: 8px; color: #667eea;"></i>
                ${cidade.nome_cidade}, MG
            </div>`
        ).join('');
        resultados.style.display = 'block';
    } else {
        resultados.innerHTML = '<div class="search-result-item">Nenhuma cidade encontrada</div>';
        resultados.style.display = 'block';
    }
});

// Fechar resultados ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-box')) {
        document.getElementById('search-results').style.display = 'none';
    }
});

// Filtrar por região
function filtrarPorRegiao() {
    const regiaoSelecionada = document.getElementById('regiao-select').value;
    const cidadeItems = document.querySelectorAll('.cidade-item[data-regiao]');
    
    cidadeItems.forEach(item => {
        if (!regiaoSelecionada || item.dataset.regiao === regiaoSelecionada) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Selecionar cidade
function selecionarCidade(cidadeId) {
    // Fechar resultados de busca
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('busca-cidade').value = '';
    
    // Atualizar URL com filtro
    const url = new URL(window.location);
    if (cidadeId) {
        url.searchParams.set('cidade', cidadeId);
    } else {
        url.searchParams.delete('cidade');
    }
    
    // Recarregar página com novo filtro
    window.location.href = url.toString();
}

// Inicializar filtros
document.addEventListener('DOMContentLoaded', function() {
    // Se há uma cidade selecionada, mostrar no campo de busca
    const cidadeSelecionada = '<?= $cidade_selecionada ?>';
    if (cidadeSelecionada) {
        const cidade = cidadesData.find(c => c.id_cidade == cidadeSelecionada);
        if (cidade) {
            document.getElementById('busca-cidade').placeholder = `Cidade selecionada: ${cidade.nome_cidade}`;
        }
    }
});
</script>


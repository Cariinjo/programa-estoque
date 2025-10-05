// Função para buscar a cor no JSON
async function fetchColor(name) {
    try {
        const resposta = await fetch('../js/bancodecores.json'); // ajuste o caminho se necessário
        if (!resposta.ok) {
            alert("Erro ao carregar o arquivo JSON!");
            return null;
        }

        const colors = await resposta.json();

        // Buscar pelo "nome" no JSON, ignorando maiúsculas/minúsculas
        const colorData = colors.find(color => color.nome.toLowerCase() === name.toLowerCase());

        if (!colorData) {
            alert("Cor não encontrada! Verifique o nome.");
            return null;
        }

        return colorData;
    } catch (error) {
        console.error('Erro ao buscar a cor:', error);
        return null;
    }
}

// Elementos do HTML
const searchButton = document.getElementById('search-button');
const colorInput = document.getElementById('color-input');

// Função para processar a busca
async function handleSearch() {
    const name = colorInput.value.trim();
    if (name) {
        const colorData = await fetchColor(name);
        if (colorData) {
            displayColor(colorData);
        }
    } else {
        alert('Por favor, digite o nome da cor');
    }
}

// Clique no botão
searchButton.addEventListener('click', handleSearch);

// Pressionar Enter no input
colorInput.addEventListener('keypress', (event) => {
    if (event.key === 'Enter') {
        handleSearch();
    }
});

// Função para exibir os dados na tela
function displayColor(data) {
    const card = document.getElementById('color-card');
    document.getElementById('color-name').textContent = data.nome;
    document.getElementById('color-hex').textContent = `Hex: ${data.hex}`;

    // Converte o hex em RGB
    const rgb = hexToRgb(data.hex);
    document.getElementById('color-rgb').textContent = `RGB: ${rgb}`;

    // Muda o fundo do card para a cor selecionada
    card.style.backgroundColor = data.hex;

    card.classList.remove('hidden');
}

// Função auxiliar para converter HEX em RGB
function hexToRgb(hex) {
    const bigint = parseInt(hex.slice(1), 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return `${r}, ${g}, ${b}`;
}

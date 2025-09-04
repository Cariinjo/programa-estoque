async function fetchColor(name) {
    try {
        const resposta = await fetch('./apidecerveja.json');
        if (!resposta.ok) {
            alert("Erro ao carregar o arquivo JSON!");
            return null;
        }

        const colors = await resposta.json();

        // Buscar pelo nome, insensível a maiúsculas/minúsculas
        const colorData = colors.find(color => color.name.toLowerCase() === name.toLowerCase());

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

// Evento de clique no botão
searchButton.addEventListener('click', async () => {
    const name = colorInput.value.trim();
    if (name) {
        const colorData = await fetchColor(name);
        if (colorData) {
            displayColor(colorData);
        }
    } else {
        alert('Por favor, digite o nome da cor');
    }
});

// Função para exibir os dados na tela
function displayColor(data) {
    const card = document.getElementById('color-card');
    document.getElementById('color-name').textContent = data.name;
    document.getElementById('color-hex').textContent = `Hex: ${data.hex}`;
    document.getElementById('color-rgb').textContent = `RGB: ${data.rgb}`;

    // muda o fundo do card para a cor selecionada
    card.style.backgroundColor = data.hex;

    card.classList.remove('hidden');
}

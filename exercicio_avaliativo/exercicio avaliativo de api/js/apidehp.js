// Função para buscar personagem na HP-API
async function fetchHP(name) {
    try {
        const response = await fetch('https://hp-api.onrender.com/api/characters');
        if (!response.ok) {
            alert("Erro ao carregar os dados da HP-API!");
            return null;
        }

        const characters = await response.json();

        // Filtra todos os personagens que contêm o texto digitado (case-insensitive)
        const matches = characters.filter(c => c.name.toLowerCase().includes(name.toLowerCase()));

        if (matches.length === 0) {
            alert("Personagem não encontrado!");
            return null;
        }

        // Retorna o primeiro match
        return matches[0];

    } catch (error) {
        console.error('Erro ao buscar personagem:', error);
        return null;
    }
}

// Elementos do HTML
const searchButton = document.getElementById('search-button');
const hpInput = document.getElementById('hp-input');

// Função para processar a busca
async function handleSearch() {
    const name = hpInput.value.trim();
    if (name) {
        const character = await fetchHP(name);
        if (character) {
            displayCharacter(character);
        }
    } else {
        alert('Digite o nome do personagem');
    }
}

// Eventos
searchButton.addEventListener('click', handleSearch);
hpInput.addEventListener('keypress', (event) => {
    if (event.key === 'Enter') {
        handleSearch();
    }
});

// Exibir personagem
function displayCharacter(data) {
    const card = document.getElementById('hp-card');
    document.getElementById('hp-nome').textContent = data.name;
    document.getElementById('hp-casa').textContent = `Casa: ${data.house || 'Desconhecida'}`;
    
    const wandWood = data.wand?.wood || 'Desconhecida';
    const wandLength = data.wand?.length || 'Desconhecida';
    document.getElementById('hp-varinha').textContent = `Varinha: ${wandWood}, ${wandLength} polegadas`;

    document.getElementById('hp-imagem').src = data.image || '';
    card.classList.remove('hidden');
}

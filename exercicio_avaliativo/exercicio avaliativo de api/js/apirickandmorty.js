async function fetchCharacter(identifier) {
    const apiUrl = `https://rickandmortyapi.com/api/character/${identifier.toLowerCase()}`;
    try {
    const resposta = await fetch(apiUrl);

    if (!resposta.ok) {
        alert("Personagem não encontrado! Verifique o ID ou nome");
        throw new Error("Personagem não encontrado");
    }

    const characterData = await resposta.json();
    return characterData;

    } catch (error) {
    console.error('Houve um erro ao buscar o personagem:', error);
    }
}

const searchButton = document.getElementById('search-button');
const characterInput = document.getElementById('rickandmorty-input');

searchButton.addEventListener('click', async () => {
    const identifier = characterInput.value;
    if (identifier) {
    const characterData = await fetchCharacter(identifier);
    if (characterData) {
        displayCharacter(characterData);
    }
    } else {
    alert('Por favor, digite o ID ou nome do personagem');
    }
});

// Função para exibir os dados na tela
function displayCharacter(data) {
    const card = document.getElementById('rickandmorty-card');
    const nameElement = document.getElementById('rickandmorty-name');
    const idElement = document.getElementById('rickandmorty-id');
    const spriteElement = document.getElementById('rickandmorty-sprite');
    const typesElement = document.getElementById('rickandmorty-types');

    nameElement.textContent = data.name;
    idElement.textContent = `#${data.id} - ${data.species}`;
    spriteElement.src = data.image;
    typesElement.textContent = `Status: ${data.status} | Gênero: ${data.gender}`;

    card.classList.remove('hidden');
}
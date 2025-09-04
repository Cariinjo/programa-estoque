async function fetchCoffee(identifier) {
    const apiUrl = `https://api.sampleapis.com/coffee/hot`;
    try {
    const resposta = await fetch(apiUrl);

    if (!resposta.ok) {
        alert("Erro ao buscar cafés");
        throw new Error("Erro na API de café");
    }

    const coffeeList = await resposta.json();
    const coffeeData = coffeeList.find(c => c.title === (identifier));

    if (!coffeeData) {
        alert("Café não encontrado! Tente outro ID");
        return null;
    }

    return coffeeData;

    } catch (error) {
    console.error('Houve um erro ao buscar o café:', error);
    }
}

const searchButton = document.getElementById('search-button');
const coffeeInput = document.getElementById('coffee-input');

searchButton.addEventListener('click', async () => {
    const identifier = coffeeInput.value;
    if (identifier) {
    const coffeeData = await fetchCoffee(identifier);
    if (coffeeData) {
        displayCoffee(coffeeData);
    }
    } else {
    alert('Por favor, digite o ID do café');
    }
});

// Exibir dados na tela
function displayCoffee(data) {
    const card = document.getElementById('coffee-card');
    const nameElement = document.getElementById('coffee-name');
    const idElement = document.getElementById('coffee-id');
    const imageElement = document.getElementById('coffee-image');
    const descriptionElement = document.getElementById('coffee-description');
    const ingredientsElement = document.getElementById('coffee-ingredients');

    nameElement.textContent = data.title;
    idElement.textContent = `ID: ${data.id}`;
    imageElement.src = data.image || "https://via.placeholder.com/300x200?text=Café";
    descriptionElement.textContent = data.description || "Sem descrição disponível.";
    ingredientsElement.textContent = `Ingredientes: ${data.ingredients?.join(', ') || "Não informados"}`;

    card.classList.remove('hidden');
}
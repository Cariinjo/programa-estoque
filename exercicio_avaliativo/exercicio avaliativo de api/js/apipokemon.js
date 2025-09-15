//a funçao precisa ser delcarada como 'async'
/*async function buscarUsuario() {
    try {
        const resposta = await fetch('https://jsonplaceholder.typicode.com/users/1');

        if(!resposta.ok) {
            throw new Error('Erro de HTTP! Status: ${resposta.status}');
        }

        //3. Pausa até a conversão para JSON terminar
        const dados = await resposta.json();

        //4. Agora podemos usar os dados
        console.log('Dados com async/await:', dados);

    } catch (erro) {
        //O try...catch substitui o .catch() do fetch
        console.error('Falha ao buscar dados:', erro);
    }
}
//Não se esqueça de chamar a função!
buscarUsuario();*/

/*import axios from "axios";
import https from "https";

const url = "https://rickandmortyapi.com/api/character/?page=19";

const agent = new https.Agent({ rejectUnauthorized: false });

async function chamarapi() {
    try {
        const resp = await axios.get(url, { httpsAgent: agent });
        console.log(resp.data);
    } catch (erro) {
        console.error("Erro:", erro);
    }
}

chamarapi();*/


// A função precisa ser declarada como 'async'
async function fetchPokemon(pokemonIdentifier) {
    const apiUrl = `https://pokeapi.co/api/v2/pokemon/${pokemonIdentifier.toLowerCase()}`;
    try {
        const resposta = await fetch(apiUrl)

        if (!resposta.ok) {

            alert("Pokémon não encontrado! verifique o nome ou número");
            throw new Error("Pokémon não encontrado");
        }

        const pokemonData = await resposta.json();
        // por enquanto, vamos apenas mostrar os dados no alert para ver se funcionou
        

        return pokemonData;
    } catch (error) {
        //4. Se qualquer passo acima falhar (problema de rede, erro 404, etc.),.
        console.error('Houve um erro ao buscar o Pokémon:', error);
    }
}

    const searchButton = document.getElementById('search-button'); //Selecionamos os elemenos do HTML com os quais vamos interagir
    const pokemonInput = document.getElementById('pokemon-input');

    searchButton.addEventListener('click', async () => { // Adicionamos um "ouvinte de evento" ao botão.
        const pokemonIdentifier = pokemonInput.value;
        if (pokemonIdentifier) {
            // Chamamos nossa função de busca e esperamos por ela
            const pokemonData = await fetchPokemon(pokemonIdentifier);

            // Se a busca foi bem-sucedida, chamamos a função para exibir os dados
            if (pokemonData) {
                displayPokemon(pokemonData);
            }
        } else {
            alert('Por favor,digite o nome ou número do Pokémon');
        }
    });

// Função para exibir os dados na tela
function displayPokemon(data) {
    // Selecionamos os elementos do card
    const card = document.getElementById('pokemon-card');
    const nameElement = document.getElementById('pokemon-name');
    const idElement = document.getElementById('pokemon-id');
    const spriteElement = document.getElementById('pokemon-sprite');
    const typesElement = document.getElementById('pokemon-types');

    // Preenchendo os dados no card
    nameElement.textContent = data.name;
    idElement.textContent = `#${data.id}`; // Navegando no objeto JSON!
    spriteElement.src = data.sprites.front_default;

    // Os tipos são um array, então precisamos processá-los
    const types = data.types.map(typeInfo => typeInfo.type.name);
    typesElement.textContent = `Tipo(s): ${types.join(', ')}`;

    // Mostramos o card removendo a classe 'hidden'
    card.classList.remove('hidden');
}

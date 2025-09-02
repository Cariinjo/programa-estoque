//a funçao precisa ser delcarada como 'async'
async function buscarUsuario() {
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
buscarUsuario();



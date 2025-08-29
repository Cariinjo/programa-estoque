const cartao = document.querySelectorAll(".carro-box");
const pedidosDeCarros = document.querySelector("#pedidos-de-carros");
const limparCompra = document.querySelector("#limpar-compra");
const campoPedido = document.querySelector("#campo-pedido");
const adicionarPedido = document.querySelector("#adicionar-pedido");
const listaPedidos = document.querySelector("#lista-pedidos");
const campoNome = document.querySelector("#nome-visitante");
const boasVindas = document.querySelector("#boas-vindas");
const nomeCracha = document.querySelector("#nome-cracha");
const body = document.body;
body.style.margin="10px";
body.style.backgroundColor="#671a9bff";

let carrinho = 0;

// Efeitos visuais + adicionar ao carrinho
cartao.forEach(item => {
  // Hover
  item.addEventListener("mouseover", () => {
    item.style.backgroundColor = "#1b48aaff";
    item.style.transform = "scale(1.05)";
    item.style.transition = "transform 0.2s";
  });

  item.addEventListener("mouseout", () => {
    item.style.transform = "scale(1)";
    item.style.backgroundColor = "grey";
  });

  // Clique no carro -> adiciona ao carrinho
  item.addEventListener("click", () => {
    if (carrinho < 10) {
      carrinho++;
      pedidosDeCarros.innerHTML = `Quantidade de carros no carrinho: ${carrinho}`;
    } else {
      alert("Você atingiu o limite de 10 pedidos!");
    }
  });
});

limparCompra.addEventListener("click", () => {
  carrinho = 0;
  pedidosDeCarros.innerHTML = `Quantidade de carros no carrinho: ${carrinho}`;
});
limparCompra.style.borderRadius = "10px";
limparCompra.style.color="red";
limparCompra.style.boxShadow="2px 2px 5px black";

adicionarPedido.addEventListener("click", () => {
    const texto = campoPedido.value.trim();
    if (texto === ""){
        alert("Digite um pedido antes de adicionar");
        return;
    }

    const li=document.createElement("li");
    li.textContent=texto;
    listaPedidos.appendChild(li);
    campoPedido.value = "";
    campoPedido.focus();
});

campoNome.addEventListener("input", () => {
const nome = campoNome.value.trim();

if (nome === "") {
    nomeCracha.textContent = "Seu nome aparecerá aqui";
} else {
    nomeCracha.textContent = `Bem-vindo, ${nome}!`;
}
});


const divi = document.createElement("div");
divi.innerHTML = "aooooba!";
document.body.appendChild(divi);

const imagem = document.createElement("img");
imagem.setAttribute("src", "../imagem/azul.jpg");
imagem.setAttribute("alt", "Imagem Azul");
divi.appendChild(imagem);

divi.style.display = "flex";
divi.style.alignItems = "center";
divi.style.justifyContent = "center";
divi.style.marginTop = "20px";
divi.style.border = "1px solid #ccc";
divi.style.padding = "10px";
divi.style.borderRadius = "5px";
divi.style.flexDirection = "column";


const cards =document.createElement("div");
cards.style.display="flex";
cards.className="cards";
cards.style.boxShadow="2px 2px 5px black";
cards.style.backgroundColor="grey";
cards.style.borderRadius="10px";
cards.style.flexDirection="row";
body.appendChild(cards);

const card1=document.createElement("div");
card1.style.width="300px";
card1.style.height="500px";
card1.style.backgroundColor="lightblue";
card1.style.borderRadius="10px";
card1.style.margin="10px";
card1.style.display="flex";
card1.style.flexDirection="column";
card1.style.alignItems="center";
cards.appendChild(card1);

const card=document.createElement("img");
card.setAttribute("src","../imagem/smigol.jpg");
card.setAttribute("alt","Imagem Smigol");
card.style.width="150px";
card.style.height="250px";
card.style.backgroundColor="lightblue";
card.style.borderRadius="10px";
card.style.margin="10px";
card1.appendChild(card);

const cardi=document.createElement("div");
cardi.style.backgroundSize="cover";
cardi.style.backgroundPosition="center";
cardi.innerHTML="personagem de senhor dos aneis";
cardi.innerHTML+="<h2>Smigol</h2>";
cardi.innerHTML+="<p>atack = 1000</p>";
cardi.innerHTML+="<p>defesa = 500</p>";
cardi.innerHTML+="<p>item = Anel</p>";
cardi.style.color="white";
cardi.style.textAlign="center";
cardi.style.backgroundColor="purple";
cardi.style.borderRadius="10px";
card1.appendChild(cardi);


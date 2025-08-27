/*let mensagem = "Hello, World!";
alert(mensagem);

function soma(a, b) {
    return a + b;
}

let num1 = Number(prompt("Digite o primeiro número:"));
let num2 = Number(prompt("Digite o segundo número:"));

alert("a soma de " + num1 + " e " + num2 + " é: " + soma(num1, num2));

let resposta = prompt("Qual é o seu nome?");

resposta = confirm("seu nome é " + resposta + "?");

if (resposta == "rafael") {
    alert("seu nome é " + resposta + "!");
}
else {
    alert("seu nome não é " + resposta + "!");
}

const resposta2 = confirm("deseja realmente sair?");
if (resposta2) {
    alert("Você escolheu sair!");
}
else {
    alert("Você não escolheu sair!");
}*/

function abrirsite(){
    window.open("https://github.com/Cariinjo","-blank");
}

function soma() {
    let num1 = Number(prompt("Digite o primeiro número:"));
    let num2 = Number(prompt("Digite o segundo número:"));
    alert("a soma de " + num1 + " e " + num2 + " é: " + (num1 + num2));
}

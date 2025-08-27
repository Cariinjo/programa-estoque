function verificarNumeroPrimo(numero) {
    if (numero <= 1) {
        alert("o numero nao e primo");
        return false;
    }
    for (let i = 2; i < numero; i++) {
        if (numero % i === 0) {
            alert("o numero nao e primo");
            return false;
        }
    }
    alert("o numero e primo");
    return true;
}

function adivinharNumero() {
    let numero = Math.floor(Math.random() * 10) + 1;
    while (true) {
        let palpite = (prompt("advinhe um numero de 0 a 10"));
        if (palpite == null) {
            alert("jogo encerrado");
            return false;
        } else if (palpite > numero) {
            alert("o numero e menor que " + palpite);
        } else if (palpite < numero) {
            alert("o numero e maior que " + palpite);
        } else {
            alert("parabens! voce adivinhou o numero " + numero);
            return false;
        }
    }
}



function jogodorobo() {
i = 100;
    while (true) {
        alert ("sua energia é " + i);
        let ação = (prompt("escolha uma ação para o robo (mover, parar, girar, atacar, defender ou sair)").toLowerCase());
        switch (ação) {
            case "mover":
                if (i < 10) {
                    alert("energia insuficiente");
                } else {
                    i=i-10;
                    alert("o robo esta se movendo e gastou 10 de energia");
                }
                break;
            case "parar":
                if (i < 10) {
                    alert("energia insuficiente");
                } else {
                    i=i-10;
                    alert("o robo parou e gastou 10 de energia");
                }
                break;
            case "girar":
                if (i < 20) {
                    alert("energia insuficiente");
                } else {
                    i=i-20;
                    alert("o robo esta girando e gastou 20 de energia");
                }
                break;
            case "atacar":
                if (i < 30) {
                    alert("energia insuficiente");
                } else {
                    i=i-30;
                    alert("o robo esta atacando e gastou 30 de energia");
                }
                break;
            case "defender":
                if (i < 50) {
                    alert("energia insuficiente");
                } else {
                    i=i-50;
                    alert("o robo esta se defendendo e gastou 50 de energia");
                }
                break;
            case "recarregar":
                if (i === 100) {
                    alert("voce sobrecarregou o robo");
                    alert("BOOOOOMM!");
                }
                else {
                    i = 100;
                    alert("o robo esta recarregando e sua energia é " + i);
                }
                break;
            case "sair":
                alert("jogo encerrado");
                return;
            default:
                alert("acao invalida");
        }
    }
}

function verificarfaixaetaria(){
    let idade = Number(prompt("Digite sua idade:"));

    let faixaEtaria=(idade < 12) ? "Criança" :
                    (idade < 18) ? "Adolescente" :
                    (idade < 60) ? "Adulto":
                    "idoso";

    alert("Sua faixa etária é: " + faixaEtaria);
}


let carro = {
        modelo: "Fusca",
        ano: 1969,
        cor: "azul"
    };
function carros(){
    for (let car in carro){
        alert("Modelo: " +carro[car].modelo + "\nAno: " +carro[car].ano + "\nCor: " +carro[car].cor);
    }
}

function adicionarCarro(){
    let modelo1 = prompt("Digite o modelo do carro:");
    let ano1 = prompt("Digite o ano do carro:");
    let cor1 = prompt("Digite a cor do carro:");
    let novoCarro = {
        modelo: modelo1,
        ano: ano1,
        cor: cor1
    };
    carro.push(novoCarro);
}

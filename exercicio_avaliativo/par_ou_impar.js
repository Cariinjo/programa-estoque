function verificarParOuImpar() {
    let num = Number(inputi.value); // converte para número

    if (isNaN(num)) {
        resultado.textContent = "Digite um número válido.";
        resultado.style.color = "black";
        return;
    }

    if (num % 2 === 0) {
        resultado.textContent = "O número é par.";
        resultado.style.color = "blue";
    } else {
        resultado.textContent = "O número é ímpar.";
        resultado.style.color = "red";
    }
}

const botton = document.querySelector("#botaoVerificar");
const resultado = document.querySelector("#resultado");
const inputi = document.querySelector("#inputNumero");

botton.addEventListener("click", verificarParOuImpar);

resultado.textContent = "";
resultado.style.width = "120px";
resultado.style.height = "20px";
resultado.style.border = "1px solid black";
resultado.style.borderRadius = "5px";
resultado.style.boxShadow = "2px 2px 5px rgba(0, 0, 0, 0.3)";
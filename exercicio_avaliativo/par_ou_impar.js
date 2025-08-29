function verificarParOuImpar(num) {
    let num = inputi.value;
    if (num % 2 === 0) {
        resultado.textContent = "O número é par.";
        resultado
    } else {
        resultado.textContent = "O número é ímpar.";
    }
}

const botton=document.querySelector("#botaoVerificar")


botton.addEventListener("click", verificarParOuImpar)

const inputi=document.querySelector("#inputNumero")



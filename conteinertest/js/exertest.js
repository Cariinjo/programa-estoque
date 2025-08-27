// Função para verificar se um número é primo
function verificarNumeroPrimo(numero) {
    if (numero <= 1) return false;
    for (let i = 2; i < numero; i++) {
        if (numero % i === 0) return false;
    }
    return true;
}

//adivinhar o numero (tentativas ate acertar)
function adivinharNumero() {
    const numeroSecreto = Math.floor(Math.random() * 100) + 1;
    let tentativas = 0;
    let acertou = false;

    while (!acertou) {
        const palpite = parseInt(prompt("Adivinhe o número entre 1 e 100:"));
        tentativas++;

        if (palpite === numeroSecreto) {
            alert(`Parabéns! Você acertou o número em ${tentativas} tentativas.`);
            acertou = true;
        } else if (palpite < numeroSecreto) {
            alert("Tente um número maior.");
        } else {
            alert("Tente um número menor.");
        }
    }
}

//validar cpf simples (tamanho e digito)

function validarCPF(cpf) {
    // Remover caracteres não numéricos
    cpf = cpf.replace(/\D/g, '');

    // Verificar se o CPF tem 11 dígitos
    if (cpf.length !== 11) return false;

    // Verificar se todos os dígitos são iguais
    if (/^(\d)\1{10}$/.test(cpf)) return false;

    // Validar dígitos verificadores
    const calcularDigito = (str, peso) => {
        let soma = 0;
        for (let i = 0; i < str.length; i++) {
            soma += parseInt(str[i]) * peso--;
        }
        const resto = soma % 11;
        return resto < 2 ? 0 : 11 - resto;
    };

    const digito1 = calcularDigito(cpf.slice(0, 9), 10);
    const digito2 = calcularDigito(cpf.slice(0, 10), 11);

    return cpf[9] == digito1 && cpf[10] == digito2;
}


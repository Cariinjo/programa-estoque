const formulario = document.querySelector("#formulario");
const resultado = document.querySelector("#resultado");
const botaoLimpar = document.querySelector("#limparDados");

// Evento de envio do formulário
formulario.addEventListener("submit", (event) => {
    event.preventDefault(); // impede o envio e reload da página

    const usuario = document.querySelector("#inputUsuario").value;
    const senha = document.querySelector("#inputSenha").value;

    // Verifica se já existe usuário e senha salvos
    const usuarioSalvo = localStorage.getItem("usuario");
    const senhaSalva = localStorage.getItem("senha");

    if (usuarioSalvo && senhaSalva) {
        // Já existe usuário e senha salvos -> validar
        if (usuario === usuarioSalvo && senha === senhaSalva) {
            resultado.textContent = "Login bem-sucedido!";
            resultado.style.color = "green";
        } else {
            resultado.textContent = "Usuário ou senha incorretos!";
            resultado.style.color = "red";
        }
    } else {
        // Não existe -> salvar pela primeira vez
        localStorage.setItem("usuario", usuario);
        localStorage.setItem("senha", senha);
        resultado.textContent = "Usuário e senha cadastrados com sucesso!";
        resultado.style.color = "blue";
    }
});

// Evento para limpar usuário e senha salvos
botaoLimpar.addEventListener("click", () => {
    localStorage.removeItem("usuario");
    localStorage.removeItem("senha");
    resultado.textContent = "Dados salvos foram apagados. Cadastre um novo usuário e senha.";
    resultado.style.color = "orange";
});

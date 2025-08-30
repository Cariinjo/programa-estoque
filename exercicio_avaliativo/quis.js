// Corrige o quiz
document.getElementById("quizForm").addEventListener("submit", function(event) {
    event.preventDefault();

    // Respostas corretas
    let corretas = {
        q1: "Tolkien",
        q2: "Frodo",
        q3: "Gandalf",
        q4: "Andúril",
        q5: "Minas Tirith",
        q6: "Théoden",
        q7: "Gollum",
        q8: "Gollum",
        q9: "Lothlórien",
        q10: "Éowyn"
    };

    let pontos = 0;
    let total = Object.keys(corretas).length;

    // Verifica cada questão
    for (let questao in corretas) {
        let resposta = document.querySelector(`input[name="${questao}"]:checked`);
        if (resposta && resposta.value === corretas[questao]) {
            pontos++;
        }
    }

    alert("Você acertou " + pontos + " de " + total + " perguntas!");
});

<?php
include 'banco.php';

$nome = $_POST['nome'];
$turma = $_POST['turma'];
$ano = $_POST['ano'];

$sql = "INSERT INTO alunos (nome, turma, ano) VALUES (?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nome, $turma, $ano);
$stmt->execute();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
</head>
<body>
    <h1>Cadastro realizado com sucesso!</h1>
    <div class="botao-voltar">
        <button onclick="window.location.href='index.html'">Voltar</button>
    </div>
</body>
</html>
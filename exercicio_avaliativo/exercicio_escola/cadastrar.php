<?php
include 'banco.php';

// Dados do Aluno
$nome = $_POST['nome'];
$turma = $_POST['turma'];
$ano = $_POST['ano'];

// Notas
$portugues = $_POST['portugues'];
$matematica = $_POST['matematica'];
$quimica = $_POST['quimica'];
$fisica = $_POST['fisica'];
$historia = $_POST['historia'];
$geografia = $_POST['geografia'];
$ed_fisica = $_POST['ed_fisica'];
$ensino_religioso = $_POST['ensino_religioso'];

// Insere o aluno primeiro
$sql_aluno = "INSERT INTO alunos (nome, turma, ano) VALUES (?, ?, ?)";
$stmt_aluno = $conn->prepare($sql_aluno);
$stmt_aluno->bind_param("ssi", $nome, $turma, $ano);
$stmt_aluno->execute();
$id_aluno = $stmt_aluno->insert_id; // Pega o ID do aluno recém-criado
$stmt_aluno->close();

// Agora insere as notas
$sql_materias = "INSERT INTO materias (portugues, matematica, quimica, fisica, historia, geografia, ed_fisica, ensino_religioso, id_aluno) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_materias = $conn->prepare($sql_materias);
$stmt_materias->bind_param("ddddddssi", $portugues, $matematica, $quimica, $fisica, $historia, $geografia, $ed_fisica, $ensino_religioso, $id_aluno);
$stmt_materias->execute();
$stmt_materias->close();

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
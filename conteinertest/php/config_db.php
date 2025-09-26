<?php
include 'banco.php';

$nome = $_POST['nome'];
$sobrenome = $_POST['sobrenome'];
$idade = $_POST['idade'];
$email = $_POST['email'];

$sql = "INSERT INTO usuarios (nome, sobrenome, idade, email) VALUES ('$nome','$sobrenome', '$idade', '$email')";


$stmt = $conn->prepare($sql); //preparar a declaração SQL
$stmt->bind_param("ssi", $nome, $sobrenome, $idade, $email); //passagem de parâmetros
$stmt->execute();


if ($conn->query($sql) === TRUE) {
    echo "Novo registro criado com sucesso!";
} else {
    echo "Erro: " . $sql . "<br>" . $conn->error;
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario</title>
    <link rel="stylesheet" href="../css//stylephp.css">
</head>
<body>
    <h1>Formulario</h1>
    <div id="pai">
        <div id="resultado">
            <?php echo "Seu nome é $nome $sobrenome, tem $idade anos e seu email é $email.";
            ?>
        </div>
    </div>
</body>
</html>
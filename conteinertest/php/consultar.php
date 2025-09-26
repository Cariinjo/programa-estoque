<?php
include 'banco.php';

$resultado = $conn->query("SELECT * FROM usuarios");

while($linha = $resultado->fetch_assoc()) {
    echo "ID: " . $linha["id"];
    echo "Nome: " . $linha["nome"];
    echo "Sobrenome: " . $linha["sobrenome"];
    echo "Idade: " . $linha["idade"];
    echo "Email: " . $linha["email"];
}

$conn->close();
?>
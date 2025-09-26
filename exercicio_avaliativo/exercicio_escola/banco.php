<?php
// Nota de segurança: Em um ambiente de produção, não use 'root' com senha em branco.
// Crie um usuário de banco de dados específico com permissões limitadas.
$host="localhost";
$user="root";
$pass="";
$db="escola";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}

// Define o charset para utf8mb4 para suportar uma gama completa de caracteres
$conn->set_charset("utf8mb4");
?>
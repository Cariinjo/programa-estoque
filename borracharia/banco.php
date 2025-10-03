<?php
// Nota de segurança: Em um ambiente de produção, não use 'root' com senha em branco.
$host="localhost";
$user="root";
$pass="";
$db="borracharia"; // <-- BANCO DE DADOS ATUALIZADO

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
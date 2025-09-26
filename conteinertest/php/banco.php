<?php
$host="localhost";
$user="root";
$pass="";
$db="crud";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}
else{
    //echo "Conexão bem-sucedida!";
}
?>
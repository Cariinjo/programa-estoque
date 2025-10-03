<?php
include 'banco.php';

$tipo = $_POST['tipo'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$valor = $_POST['valor'] ?? 0;

if (($tipo == 'Entrada' || $tipo == 'Saída') && !empty($descricao) && $valor > 0) {
    $sql = "INSERT INTO fluxo_caixa (tipo, descricao, valor) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssd', $tipo, $descricao, $valor);
    $stmt->execute();
}
header('Location: caixa.php');
?>
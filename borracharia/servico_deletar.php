<?php
include 'banco.php';
$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "DELETE FROM servicos WHERE id_servico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) header('Location: servicos.php?msg=Serviço excluído!');
    else header('Location: servicos.php?err=Erro. O serviço pode estar em uso em uma venda.');
}
$conn->close();
?>
<?php
include 'banco.php';
$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "DELETE FROM fornecedores WHERE id_fornecedor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header('Location: fornecedores.php?msg=Fornecedor excluído!');
    } else {
        header('Location: fornecedores.php?err=Erro. O fornecedor pode estar associado a uma compra.');
    }
} else {
    header('Location: fornecedores.php');
}
?>
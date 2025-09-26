<?php
include 'banco.php';

$id_recebido = $_GET['id'] ?? null;

// Verifica se o ID foi fornecido e é um número
if ($id_recebido && is_numeric($id_recebido)) {
    $id_aluno = (int)$id_recebido;

    $sql = "DELETE FROM alunos WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_aluno);

        if ($stmt->execute()) {
            // Redireciona para a página de consulta após a exclusão bem-sucedida
            header("Location: consultar.php");
            exit();
        } else {
            echo "Erro ao deletar o aluno: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Erro ao preparar a consulta: " . $conn->error;
    }
} else {
    echo "ID do aluno inválido ou não fornecido.";
}

$conn->close();
?>
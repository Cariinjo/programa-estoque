<?php
include 'banco.php';

$aluno_id = $_POST['id_aluno'] ?? null;

$nota = $_POST['nota'] !== "" ? (float) $_POST['nota'] : null;

if ($aluno_id && $nota !== null) {
    $stmt = $conn -> prepare("UPDATE materias SET nota = ? WHERE id_aluno = ?");
    $stmt -> bind_param("di", $nota, $aluno_id);
    $stmt -> execute();
}
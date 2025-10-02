<?php
include 'banco.php';

$id_aluno = $_POST['id'] ?? null;
$nome = $_POST['nome'] ?? null;
$turma = $_POST['turma'] ?? null;
$ano = $_POST['ano'] ?? null;

// Notas
$portugues = $_POST['portugues'] ?? null;
$matematica = $_POST['matematica'] ?? null;
$quimica = $_POST['quimica'] ?? null;
$fisica = $_POST['fisica'] ?? null;
$historia = $_POST['historia'] ?? null;
$geografia = $_POST['geografia'] ?? null;
$ed_fisica = $_POST['ed_fisica'] ?? null;
$ensino_religioso = $_POST['ensino_religioso'] ?? null;

$aluno = null;
$mensagem = '';
$erro = false;

// LÓGICA DE ATUALIZAÇÃO: Verifica se todos os dados foram enviados
if ($id_aluno && $nome && $turma && is_numeric($ano) && isset($portugues)) {
    // Atualiza tabela alunos
    $sql_update_aluno = "UPDATE alunos SET nome = ?, turma = ?, ano = ? WHERE ID = ?"; // CORRIGIDO: id -> ID
    $stmt_update_aluno = $conn->prepare($sql_update_aluno);
    $stmt_update_aluno->bind_param("ssii", $nome, $turma, $ano, $id_aluno);
    
    // Atualiza tabela materias
    $sql_update_materias = "UPDATE materias SET portugues = ?, matematica = ?, quimica = ?, fisica = ?, historia = ?, geografia = ?, ed_fisica = ?, ensino_religioso = ? WHERE id_aluno = ?";
    $stmt_update_materias = $conn->prepare($sql_update_materias);
    $stmt_update_materias->bind_param("ddddddssi", $portugues, $matematica, $quimica, $fisica, $historia, $geografia, $ed_fisica, $ensino_religioso, $id_aluno);

    if ($stmt_update_aluno->execute() && $stmt_update_materias->execute()) {
        $mensagem = "Aluno e notas atualizados com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar: " . $conn->error;
        $erro = true;
    }
    $stmt_update_aluno->close();
    $stmt_update_materias->close();
} 
// LÓGICA DE BUSCA: Busca o aluno e suas notas para edição
elseif ($id_aluno && is_numeric($id_aluno)) {
    $sql_select = "SELECT a.*, m.* FROM alunos a LEFT JOIN materias m ON a.ID = m.id_aluno WHERE a.ID = ?"; // CORRIGIDO: a.id -> a.ID
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id_aluno);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    
    if ($result->num_rows > 0) {
        $aluno = $result->fetch_assoc();
    } else {
        $mensagem = "Aluno com o ID informado não foi encontrado.";
        $erro = true;
    }
    $stmt_select->close();
}
// Se nenhum ID válido foi postado
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$id_aluno) {
    $mensagem = "ID do aluno não foi fornecido.";
    $erro = true;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container2">
        <h1>Editar Aluno</h1>

        <?php if ($mensagem): ?>
            <p style="color: <?php echo $erro ? 'red' : 'green'; ?>;"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <?php if ($aluno): ?>
            <form action="atualizar.php" method="POST">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($aluno['ID']); ?>"> <h3>Dados Pessoais</h3>
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>

                <label for="turma">Turma:</label>
                <input type="text" id="turma" name="turma" value="<?php echo htmlspecialchars($aluno['turma']); ?>" required>

                <label for="ano">Ano:</label>
                <input type="number" id="ano" name="ano" value="<?php echo htmlspecialchars($aluno['ano']); ?>" required>

                <h3>Notas</h3>
                <label for="portugues">Português:</label>
                <input type="number" id="portugues" name="portugues" value="<?php echo htmlspecialchars($aluno['portugues'] ?? ''); ?>" required>

                <label for="matematica">Matemática:</label>
                <input type="number" id="matematica" name="matematica" value="<?php echo htmlspecialchars($aluno['matematica'] ?? ''); ?>" required>

                <label for="quimica">Química:</label>
                <input type="number"  id="quimica" name="quimica" value="<?php echo htmlspecialchars($aluno['quimica'] ?? ''); ?>" required>

                <label for="fisica">Física:</label>
                <input type="number"  id="fisica" name="fisica" value="<?php echo htmlspecialchars($aluno['fisica'] ?? ''); ?>" required>

                <label for="historia">História:</label>
                <input type="number"  id="historia" name="historia" value="<?php echo htmlspecialchars($aluno['historia'] ?? ''); ?>" required>

                <label for="geografia">Geografia:</label>
                <input type="number"  id="geografia" name="geografia" value="<?php echo htmlspecialchars($aluno['geografia'] ?? ''); ?>" required>

                <label for="ed_fisica">Educação Física:</label>
                <input type="text" id="ed_fisica" name="ed_fisica" value="<?php echo htmlspecialchars($aluno['ed_fisica'] ?? ''); ?>" required>

                <label for="ensino_religioso">Ensino Religioso:</label>
                <input type="text" id="ensino_religioso" name="ensino_religioso" value="<?php echo htmlspecialchars($aluno['ensino_religioso'] ?? ''); ?>" required>

                <button type="submit">Atualizar</button>
            </form>
        <?php elseif (!$mensagem && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <p>Para editar um aluno, por favor, volte e insira um ID na página anterior.</p>
        <?php endif; ?>

        <br>
        <?php if ($mensagem && !$erro): ?>
            <button onclick="location.href='consultar.php'">Ver Lista de Alunos</button>
        <?php else: ?>
            <button onclick="location.href='atualizarhtml.php'">Voltar</button>
        <?php endif; ?>
    </div>
</body>
</html>
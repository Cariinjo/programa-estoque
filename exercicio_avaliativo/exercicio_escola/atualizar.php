<?php
// ... seu código PHP de atualização ...
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
    <div class="container">
        <h1>Editar Aluno</h1>
        <?php if ($mensagem): ?>
            <p style="color: <?php echo $erro ? '#dc3545' : '#28a745'; ?>; font-weight: bold;"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>
        <?php if ($aluno): ?>
            <form action="atualizar.php" method="POST">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($aluno['id']); ?>">
                
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>

                <label for="turma">Turma:</label>
                <input type="text" id="turma" name="turma" value="<?php echo htmlspecialchars($aluno['turma']); ?>" required>

                <label for="ano">Ano:</label>
                <input type="number" id="ano" name="ano" value="<?php echo htmlspecialchars($aluno['ano']); ?>" required>
                
                <button type="submit">Atualizar</button>
            </form>
        <?php endif; ?>
        
        <?php if ($mensagem && !$erro): ?>
             <a href='consultar.php' class="btn btn-secondary">Ver Lista</a>
        <?php else: ?>
            <a href='atualizar.html' class="btn btn-secondary">Voltar</a>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
include 'banco.php';
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$fornecedor = $stmt->get_result()->fetch_assoc();
if (!$fornecedor) { header('Location: fornecedores.php'); exit; }
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Atualizar Fornecedor</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container">
    <h1>Atualizar Fornecedor</h1>
    <form action="fornecedor_action.php" method="POST">
        <input type="hidden" name="acao" value="atualizar">
        <input type="hidden" name="id_fornecedor" value="<?php echo $fornecedor['id_fornecedor']; ?>">
        <label>Nome da Empresa:</label><input type="text" name="nome" value="<?php echo htmlspecialchars($fornecedor['nome']); ?>" required>
        <label>Nome do Contato:</label><input type="text" name="contato_nome" value="<?php echo htmlspecialchars($fornecedor['contato_nome']); ?>">
        <label>Telefone:</label><input type="text" name="telefone" value="<?php echo htmlspecialchars($fornecedor['telefone']); ?>">
        <label>Email:</label><input type="email" name="email" value="<?php echo htmlspecialchars($fornecedor['email']); ?>">
        <button type="submit">Atualizar</button>
    </form>
    <br><button onclick="location.href='fornecedores.php'">Cancelar</button>
</div>
</body></html>
<?php
include 'banco.php';

$acao = $_POST['acao'] ?? '';

if ($acao === 'cadastrar') {
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';

    if (!empty($nome)) {
        $sql = "INSERT INTO clientes (nome, telefone, endereco) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nome, $telefone, $endereco);
        
        if ($stmt->execute()) {
            header('Location: clientes.php?msg=Cliente cadastrado com sucesso!');
        } else {
            header('Location: clientes.php?err=Erro ao cadastrar cliente.');
        }
        $stmt->close();
    } else {
        header('Location: cliente_cadastrar.php?err=O nome é obrigatório.');
    }

} elseif ($acao === 'atualizar') {
    $id = $_POST['id_cliente'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $saldo_devedor = $_POST['saldo_devedor'] ?? 0.00;

    if ($id && !empty($nome)) {
        $sql = "UPDATE clientes SET nome = ?, telefone = ?, endereco = ?, saldo_devedor = ? WHERE id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdi", $nome, $telefone, $endereco, $saldo_devedor, $id);

        if ($stmt->execute()) {
            header('Location: clientes.php?msg=Cliente atualizado com sucesso!');
        } else {
            header('Location: clientes.php?err=Erro ao atualizar cliente.');
        }
        $stmt->close();
    } else {
        header('Location: clientes.php?err=Dados inválidos para atualização.');
    }
}

$conn->close();
?>
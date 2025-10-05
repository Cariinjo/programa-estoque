<?php
session_start();
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') die("Acesso negado.");
include 'banco.php';

$acao = $_REQUEST['acao'] ?? 'listar'; // Pega ação da URL ou do form

switch ($acao) {
    case 'novo':
    case 'editar':
        $custo = null;
        if ($acao == 'editar') {
            $id = $_GET['id'];
            $stmt = $conn->prepare("SELECT * FROM custos_fixos WHERE id_custo_fixo = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $custo = $stmt->get_result()->fetch_assoc();
        }
        // Exibe o formulário de cadastro/edição
        echo '<!DOCTYPE html><html lang="pt-br"><head><title>Gerenciar Custo Fixo</title><link rel="stylesheet" href="style.css"></head><body>';
        echo '<div class="container">';
        echo '<h1>' . ($acao == 'novo' ? 'Adicionar Custo Fixo' : 'Editar Custo Fixo') . '</h1>';
        echo '<form action="custo_fixo_action.php" method="POST">';
        echo '<input type="hidden" name="acao" value="' . ($acao == 'novo' ? 'salvar' : 'atualizar') . '">';
        if ($custo) echo '<input type="hidden" name="id_custo_fixo" value="' . $custo['id_custo_fixo'] . '">';
        echo '<label>Descrição:</label><input type="text" name="descricao" value="' . htmlspecialchars($custo['descricao'] ?? '') . '" required>';
        echo '<label>Valor Mensal (R$):</label><input type="number" step="0.01" name="valor_mensal" value="' . ($custo['valor_mensal'] ?? '') . '" required>';
        echo '<br><button type="submit">Salvar</button>';
        echo '</form>';
        echo '<br><button onclick="location.href=\'custos_fixos.php\'">Cancelar</button>';
        echo '</div></body></html>';
        break;

    case 'salvar':
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor_mensal'];
        $stmt = $conn->prepare("INSERT INTO custos_fixos (descricao, valor_mensal) VALUES (?, ?)");
        $stmt->bind_param("sd", $descricao, $valor);
        $stmt->execute();
        header('Location: custos_fixos.php');
        break;

    case 'atualizar':
        $id = $_POST['id_custo_fixo'];
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor_mensal'];
        $stmt = $conn->prepare("UPDATE custos_fixos SET descricao = ?, valor_mensal = ? WHERE id_custo_fixo = ?");
        $stmt->bind_param("sdi", $descricao, $valor, $id);
        $stmt->execute();
        header('Location: custos_fixos.php');
        break;

    case 'deletar':
        $id = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM custos_fixos WHERE id_custo_fixo = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Location: custos_fixos.php');
        break;
}

$conn->close();
?>
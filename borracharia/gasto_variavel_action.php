<?php
session_start();
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') die("Acesso negado.");
include 'banco.php';

// Usa $_REQUEST para pegar a 'acao' tanto de links (GET) quanto de formulários (POST)
$acao = $_REQUEST['acao'] ?? 'listar';

switch ($acao) {
    case 'novo':
    case 'editar':
        $gasto = null;
        if ($acao == 'editar') {
            $id = $_GET['id'];
            $stmt = $conn->prepare("SELECT * FROM despesas WHERE id_despesa = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $gasto = $stmt->get_result()->fetch_assoc();
        }
        // Exibe o formulário de HTML para adicionar ou editar
        echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Gerenciar Gasto Variável</title><link rel="stylesheet" href="style.css"></head><body>';
        echo '<div class="container">';
        echo '<h1>' . ($acao == 'novo' ? 'Adicionar Gasto Variável' : 'Editar Gasto Variável') . '</h1>';
        echo '<form action="gasto_variavel_action.php" method="POST">';
        echo '<input type="hidden" name="acao" value="' . ($acao == 'novo' ? 'salvar' : 'atualizar') . '">';
        if ($gasto) echo '<input type="hidden" name="id_despesa" value="' . $gasto['id_despesa'] . '">';
        
        echo '<label>Descrição:</label><input type="text" name="descricao" value="' . htmlspecialchars($gasto['descricao'] ?? '') . '" required>';
        echo '<label>Valor (R$):</label><input type="number" step="0.01" name="valor" value="' . ($gasto['valor'] ?? '') . '" required>';
        echo '<label>Data:</label><input type="date" name="data_despesa" value="' . ($gasto['data_despesa'] ?? date('Y-m-d')) . '" required>';
        echo '<label>Categoria:</label><input type="text" name="categoria" value="' . htmlspecialchars($gasto['categoria'] ?? '') . '">';
        
        echo '<br><button type="submit">Salvar</button>';
        echo '</form>';
        echo '<br><button onclick="location.href=\'gastos_variaveis.php\'">Cancelar</button>';
        echo '</div></body></html>';
        break;

    case 'salvar': // Ação de salvar um novo gasto
        $stmt = $conn->prepare("INSERT INTO despesas (descricao, valor, data_despesa, categoria) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $_POST['descricao'], $_POST['valor'], $_POST['data_despesa'], $_POST['categoria']);
        $stmt->execute();
        header('Location: gastos_variaveis.php');
        break;

    case 'atualizar': // Ação de atualizar um gasto existente
        $stmt = $conn->prepare("UPDATE despesas SET descricao = ?, valor = ?, data_despesa = ?, categoria = ? WHERE id_despesa = ?");
        $stmt->bind_param("sdssi", $_POST['descricao'], $_POST['valor'], $_POST['data_despesa'], $_POST['categoria'], $_POST['id_despesa']);
        $stmt->execute();
        header('Location: gastos_variaveis.php');
        break;

    case 'deletar': // Ação de deletar um gasto
        $stmt = $conn->prepare("DELETE FROM despesas WHERE id_despesa = ?");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        header('Location: gastos_variaveis.php');
        break;
}

$conn->close();
?>
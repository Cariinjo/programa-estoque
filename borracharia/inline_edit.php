<?php
session_start();
// Apenas Admins podem fazer edições
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') {
    // Retorna um erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
    exit();
}
include 'banco.php';

// Pega os dados enviados via AJAX (JavaScript)
$id = $_POST['id'] ?? 0;
$column = $_POST['column'] ?? '';
$value = $_POST['value'] ?? '';
$table = $_POST['table'] ?? '';

// --- MEDIDA DE SEGURANÇA CRUCIAL ---
// Lista branca de tabelas e colunas que podem ser editadas
$allowed_tables = [
    'produtos' => ['nome', 'marca', 'medida', 'quantidade_estoque', 'preco_venda'],
    'clientes' => ['nome', 'telefone', 'endereco'],
    'servicos' => ['nome', 'preco_venda'],
];

if (!array_key_exists($table, $allowed_tables) || !in_array($column, $allowed_tables[$table])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Operação de edição não permitida para esta coluna/tabela.']);
    exit();
}

// Determina a coluna de ID com base no nome da tabela
$id_column = 'id_produto'; // Padrão para a tabela de produtos
if ($table == 'clientes') {
    $id_column = 'id_cliente';
} elseif ($table == 'servicos') {
    $id_column = 'id_servico';
}
// Adicione outras exceções se necessário

// Monta e executa a atualização de forma segura
$sql = "UPDATE `$table` SET `$column` = ? WHERE `$id_column` = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Erro na preparação da query
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erro ao preparar a consulta SQL.']);
    exit();
}


// Determina o tipo de parâmetro (s = string, i = integer, d = double)
$type = 's';
if (is_numeric($value)) {
    if (strpos($value, '.') !== false || strpos($value, ',') !== false) {
        $type = 'd'; // double
        $value = str_replace(',', '.', $value); // Converte vírgula para ponto
    } else {
        $type = 'i'; // integer
    }
}
$stmt->bind_param($type . 'i', $value, $id);

$response = [];
if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = 'Atualizado com sucesso!';
} else {
    $response['status'] = 'error';
    $response['message'] = 'Erro ao executar a atualização: ' . $stmt->error;
}

$stmt->close();
$conn->close();

// Retorna a resposta para o JavaScript
header('Content-Type: application/json');
echo json_encode($response);
?>
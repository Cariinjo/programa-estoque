<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Alunos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Alunos Cadastrados</h1>
        <input type="text" id="campoFiltro" onkeyup="filtrarTabela()" placeholder="Digite um nome para filtrar...">
        <div style="overflow-x:auto;"> <table id="tabelaAlunos">
                </table>
        </div>
        <a href="index.html" class="btn btn-secondary">Voltar</a>
    </div>
    </body>
</html>
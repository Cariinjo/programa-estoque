<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo à Borracharia</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .choice-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 80vh;
            text-align: center;
        }
        .choice-buttons {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .choice-button {
            padding: 20px 40px;
            font-size: 1.2em;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .choice-button.register {
            background-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="choice-container">
        <h1>Bem-vindo ao Sistema da Borracharia</h1>
        <p>Selecione uma opção para continuar</p>
        <div class="choice-buttons">
            <a href="login.php" class="choice-button">Fazer Login</a>
            <a href="login.php" class="choice-button register">Cadastrar</a>
        </div>
    </div>
</body>
</html>
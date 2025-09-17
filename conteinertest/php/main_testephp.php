<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dia = $_POST["dia"];
    $nome = $_POST["nome"];
    $idade = $_POST["idade"];


    switch ($dia) {
        case "segunda":
            echo "inicio da semana";
            break;
        case "sexta":
            echo "ultimo dia da semana";
            break;
        case "sabado":
        case "domingo":
            echo "final de semana";
            break;
        case "terca":
        case "quarta":
        case "quinta":
            echo "meio da semana";
            break;
        default:
            echo "dia invalido";
    }
    echo "<br>";
    if ($idade > 18 && ($dia == "sexta" || $dia == "sabado" || $dia == "domingo")) {
        echo "$nome é Maior de idade, hora de meter o loko";
    }
    else if ($idade >= 18) {
        echo "$nome é Maior de idade, mas não é fim de semana, pode jogar";
    }
    else {
        echo "$nome é Menor de idade, vai dormir";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario</title>
    <link rel="stylesheet" href="../css//stylephp.css">
</head>
<body>
    <h1>Formulario</h1>
    <div id="pai">
        <div id="resultado">
            <?php echo "Meu nome é $nome, tenho $idade anos e hoje é $dia.";
            ?>
        </div>
    </div>
</body>
</html>



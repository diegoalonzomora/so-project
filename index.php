<?php

$mensaje = "Hola desde PHP!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi primera página con PHP</title>
</head>
<body>

    <h1>Hola Mundo</h1>


    <p><?php echo $mensaje; ?></p>

    <p><?= "Este texto también viene de PHP"; ?></p>
</body>
</html>

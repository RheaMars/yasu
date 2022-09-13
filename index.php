<?php
spl_autoload_register(function ($class) {
    include str_replace("\\", "/", $class) . '.php';
});

use src\models\Playboard;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sudoku</title>
    <link href="css/playboard.css" rel="stylesheet">
</head>
<body>
    <h1>Sudoku</h1>

    <?php
    $playboard = new Playboard(3);
    echo $playboard->generatePlayboardHtml();
    ?>

</body>
</html>
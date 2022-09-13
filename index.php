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
    <script src="js/ext/jquery-3.6.1.js"></script>
    <script src="js/app.js"></script>
</head>
<body>
    <h1>Sudoku</h1>

    <div id="playboardWrapper">
        <?php
        $playboard = new Playboard(3);
        echo $playboard->generatePlayboardHtml();
        ?>
    </div>

    <div>
        <button id="createNewGame">New game</button>
    </div>

</body>
</html>
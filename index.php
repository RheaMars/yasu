<?php
spl_autoload_register(function ($class) {
    include str_replace("\\", "/", $class) . '.php';
});

use src\models\Game;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sudoku</title>
    <link href="css/playboard.css" rel="stylesheet">
    <script src="js/ext/jquery-3.6.1.min.js"></script>
    <script src="js/app.js"></script>
</head>
<body>
    <h1>Sudoku</h1>

    <div id="playboardWrapper">
        <?php
            $game = new Game();
            echo $game->initializeGame(3);
        ?>
    </div>

    <div class="mt-10">
        <button id="createNewGame">New game</button>
        <label>with base size</label>
        <select id="selectBaseSize">
            <option value="2">2</option>
            <option value="3" selected>3</option>
            <option value="4">4</option>
        </select>
    </div>

    <div class="mt-10">
        <button id="restartGame">Restart game</button>
    </div>

</body>
</html>
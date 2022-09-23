<?php
spl_autoload_register(function ($class) {
    include str_replace("\\", "/", $class) . '.php';
});
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

    <div class="mt-10">
        <button id="createNewGame">New game</button>
        <label for="selectBaseSize">with base size</label>
        <select id="selectBaseSize">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3" selected>3</option>
            <option value="4">4</option>
            <option value="5">5</option>
        </select>
    </div>

    <div class="mt-10">
        <img style="display:none" id="loadingGif" src="img/loading.gif" alt="loading"/>
    </div>

    <div class="mt-10" id="playboardWrapper"></div>

    <div class="mt-10">
        <button style="display:none" id="validateGame" class="toggleOnCreateGame">Validate game</button>
    </div>

    <div class="mt-10">
        <button style="display:none" id="restartGame" class="toggleOnCreateGame">Restart game</button>
    </div>

    <div class="mt-10">
        <button style="display:none" id="solveGame" class="toggleOnCreateGame">Solve game</button>
    </div>

</body>
</html>
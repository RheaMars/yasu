<?php

namespace src\actions;

spl_autoload_register(function ($class) {
    include "../../" . str_replace("\\", "/", $class) . '.php';
});

use src\models\Game;

$game = new Game();
echo $game->initializeGame(3);
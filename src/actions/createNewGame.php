<?php
declare(strict_types=1);

namespace src\actions;

spl_autoload_register(function ($class) {
    include "../../" . str_replace("\\", "/", $class) . '.php';
});

use src\models\Game;

$baseSize = (int)$_POST["baseSize"];

$game = new Game();
echo $game->initializeGame($baseSize);
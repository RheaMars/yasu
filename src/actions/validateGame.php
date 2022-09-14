<?php

namespace src\actions;

use src\models\Playboard;

spl_autoload_register(function ($class) {
    include "../../" . str_replace("\\", "/", $class) . '.php';
});

// use src\models\Game;

$fieldData = $_POST["fieldData"];
$numberOfFields = $_POST["numberOfFields"];

$playboard = new Playboard();
$playboard->initializeFromData($fieldData);

if ($playboard->isValid()){
    echo 1;
}
else {
    echo 0;
}
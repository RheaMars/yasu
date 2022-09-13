<?php

namespace src\actions;

spl_autoload_register(function ($class) {
    include "../../" . str_replace("\\", "/", $class) . '.php';
});

use src\models\Playboard;

$playboard = new Playboard(3);
$playboardHtml = $playboard->generatePlayboardHtml();

echo $playboardHtml;
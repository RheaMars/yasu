<?php
declare(strict_types=1);

namespace src\actions;

use src\models\Playboard;

spl_autoload_register(function ($class) {
    include "../../" . str_replace("\\", "/", $class) . '.php';
});

$fieldData = json_decode($_POST["fieldData"], true);

$baseSize = (int)pow(sizeof($fieldData), 1/4);
$playboard = new Playboard($baseSize);
$playboard->setFieldsFromData($fieldData);

$invalidFieldsPreparedForHtml = $playboard->getFieldsPreparedForHtml($playboard->getInvalidFields());

echo json_encode($invalidFieldsPreparedForHtml);

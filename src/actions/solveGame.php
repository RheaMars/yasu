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

// validate current state of game first
$invalidFields = $playboard->getInvalidFields();

if (sizeof($invalidFields) > 0) {
    $invalidFieldsPreparedForHtml = $playboard->getFieldsPreparedForHtml($invalidFields);
    echo json_encode(["status" => "invalid", "invalidFields" => $invalidFieldsPreparedForHtml]);
}
else {
    $isSolved = $playboard->solve();

    if ($isSolved) {
        echo json_encode(["status" => "solved", "fields" => $playboard->getFieldsPreparedForHtml($playboard->getFields())]);
    }
    else {
        echo json_encode(["status" => "unsolvable", "fields" => $playboard->getFieldsPreparedForHtml($playboard->getNonEmptyUnfixedFields())]);
    }
}


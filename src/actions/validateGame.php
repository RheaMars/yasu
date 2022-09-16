<?php

namespace src\actions;

use src\models\Playboard;

spl_autoload_register(function ($class) {
    include "../../" . str_replace("\\", "/", $class) . '.php';
});

$fieldData = $_POST["fieldData"];

$playboard = new Playboard();
$playboard->initializeFromData($fieldData);

$invalidFields = $playboard->getInvalidFields();

$invalidFieldsPreparedForHtml = [];
foreach ($invalidFields as $field) {
    $invalidFieldsPreparedForHtml[] = [
        "row" => $field->getRowIndex(),
        "col" => $field->getColIndex()
    ];
}

echo json_encode($invalidFieldsPreparedForHtml);

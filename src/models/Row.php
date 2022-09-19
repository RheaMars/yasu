<?php
declare(strict_types=1);

namespace src\models;

use Exception;

class Row extends ValueGroup
{
    public function __construct(int $index, int $baseSize)
    {
        parent::__construct($index, $baseSize);
        $this->type = "row";
    }

    public function replaceValuesbyRow(Row $other): void
    {
        $thisFields = $this->getFields();
        $otherFields = $other->getFields();

        if (sizeof($thisFields) != sizeof($otherFields)){
            throw new Exception("Cannot replace the values by a row of unequal length (this length " . sizeof($thisFields) . ", other length ". sizeof($otherFields) . ")");
        }

        $otherValues = $otherFields->getValues();

        for ($i = 0; $i < sizeof($thisFields); $i++){
            $thisFields[$i]->setValue($otherValues[$i]);
        }
    }
}
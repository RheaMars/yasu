<?php
namespace src\models;

class Row extends DigitGroup
{
    public function __construct(int $index)
    {
        parent::__construct($index);
        $this->type = "row";
    }
}
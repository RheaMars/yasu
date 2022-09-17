<?php

namespace src\models;

class Column extends DigitGroup
{
    public function __construct(int $index, int $baseSize)
    {
        parent::__construct($index, $baseSize);
        $this->type = "column";
    }
}
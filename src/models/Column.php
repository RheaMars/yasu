<?php

namespace src\models;

class Column extends DigitGroup
{
    public function __construct(int $index)
    {
        parent::__construct($index);
        $this->type = "column";
    }
}
<?php

namespace src\models;

class Block extends DigitGroup
{
    public function __construct(string $index)
    {
        parent::__construct($index);
        $this->type = "block";
    }
}
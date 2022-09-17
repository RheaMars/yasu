<?php
namespace src\models;

class Row extends ValueGroup
{
    public function __construct(int $index, int $baseSize)
    {
        parent::__construct($index, $baseSize);
        $this->type = "row";
    }
}
<?php
declare(strict_types=1);

namespace src\models;

class Column extends ValueGroup
{
    public function __construct(int $index, int $baseSize)
    {
        parent::__construct($index, $baseSize);
        $this->type = "column";
    }
}
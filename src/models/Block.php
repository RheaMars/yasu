<?php

namespace src\models;

class Block extends DigitGroup
{
    private int $playboardRowIndex;

    private int $playboardColIndex;

    public function __construct(string $index, int $playboardRowIndex, int $playboardColIndex)
    {
        parent::__construct($index);
        $this->type = "block";

        $this->playboardRowIndex = $playboardRowIndex;
        $this->playboardColIndex = $playboardColIndex;
    }

    public function getPlayboardRowIndex(): int
    {
        return $this->playboardRowIndex;
    }

    public function getPlayboardColIndex(): int
    {
        return $this->playboardColIndex;
    }
}
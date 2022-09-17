<?php

namespace src\models;

class Field
{
    private int $rowIndex;

    private int $colIndex;

    // a row of blocks
    private int $playboardRowIndex;

    private int $playboardColIndex;

    // a row within a block
    private int $blockRowIndex;

    private int $blockColIndex;

    private Digit $digit;

    private bool $isValueFixed;

    public function __construct(int $baseSize, int $rowIndex, int $colIndex, Digit $digit, bool $isValueFixed = false)
    {
        $this->rowIndex = $rowIndex;
        $this->colIndex = $colIndex;
        $this->digit = $digit;
        $this->isValueFixed = $isValueFixed;

        $rowQuotient = (int)($this->rowIndex / $baseSize);
        $rowRemainder = $this->rowIndex % $baseSize;
        $this->playboardRowIndex = $rowRemainder === 0 ? $rowQuotient : $rowQuotient + 1;
        $this->blockRowIndex = $rowRemainder === 0 ? $baseSize : $rowRemainder;

        $colQuotient = (int)($this->colIndex / $baseSize);
        $colRemainder = $this->colIndex % $baseSize;
        $this->playboardColIndex = $colRemainder === 0 ? $colQuotient : $colQuotient + 1;
        $this->blockColIndex = $colRemainder === 0 ? $baseSize : $colRemainder;

    }

    public function __toString(): string
    {
        return "Row: " . $this->rowIndex . "<br/>" .
            "Col: " . $this->colIndex . "<br/>" .
            "BlockRow: " . $this->blockRowIndex . "<br/>" .
            "BlockCol: " . $this->blockColIndex . "<br/>" .
            "PlayboardRow: " . $this->playboardRowIndex . "<br/>" .
            "PlayboardCol: " . $this->playboardColIndex . "<br/>" .
            "Digit-Value: " . $this->digit->getValue() . "<br/>" .
            "IsValueFixed: " . $this->isValueFixed;
    }

    public function getRowIndex(): int
    {
        return $this->rowIndex;
    }

    public function getColIndex(): int
    {
        return $this->colIndex;
    }

    public function getBlockIndex(): string
    {
        return $this->playboardRowIndex."-".$this->playboardColIndex;
    }

    public function getPlayboardRowIndex(): int
    {
        return $this->playboardRowIndex;
    }

    public function getPlayboardColIndex(): int
    {
        return $this->playboardColIndex;
    }

    public function getBlockRowIndex(): int
    {
        return $this->blockRowIndex;
    }

    public function getBlockColIndex(): int
    {
        return $this->blockColIndex;
    }

    public function getDigit(): Digit
    {
        return $this->digit;
    }

    public function setDigit(Digit $digit): void
    {
        $this->digit = $digit;
    }

    public function setToFixed(): void
    {
        $this->isValueFixed = true;
    }

    public function isValueFixed(): bool
    {
        return $this->isValueFixed;
    }
}
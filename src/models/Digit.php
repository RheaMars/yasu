<?php

namespace src\models;

class Digit
{
    private ?int $value;

    public function __construct(?int $value)
    {
        $this->value = $value;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public static function getRandomDigit(int $baseSize): Digit
    {
        return new Digit(rand(1, pow($baseSize, 2)));
    }
}
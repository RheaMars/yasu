<?php

namespace src\models;

use Exception;

class Digit
{
    private ?int $value;

    public function __construct(?int $value, $baseSize)
    {
        if (isset($value) && ($value < 1 || pow($baseSize, 2) < $value)) {
            throw new Exception("Non-empty values must be between 1 and ".pow($baseSize, 2));
        }
        $this->value = $value;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public static function getRandomDigit(int $baseSize): Digit
    {
        return new Digit(rand(1, pow($baseSize, 2)), $baseSize);
    }
}
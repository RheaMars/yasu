<?php
namespace src\collections;

use ArrayObject;
use InvalidArgumentException;

class ValueCollection extends ArrayObject
{
    public function offsetSet($index, $value): void
    {
        if (!is_int($value) && null !== $value) {
            throw new InvalidArgumentException("Input must be null or of type int.");
        }

        parent::offsetSet($index, $value);
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
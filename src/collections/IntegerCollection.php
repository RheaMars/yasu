<?php
namespace src\collections;

use ArrayObject;
use InvalidArgumentException;

class IntegerCollection extends ArrayObject
{
    public function offsetSet($index, $int): void
    {
        if (!is_int($int)) {
            throw new InvalidArgumentException("Input must be of type int");
        }

        parent::offsetSet($index, $int);
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
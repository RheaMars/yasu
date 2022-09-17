<?php
namespace src\collections;

use ArrayObject;
use InvalidArgumentException;
use src\models\Column;

class ColumnCollection extends ArrayObject
{
    public function offsetSet($index, $col): void
    {
        if (!($col instanceof Column)) {
            throw new InvalidArgumentException("Input must be of type Column");
        }

        parent::offsetSet($index, $col);
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
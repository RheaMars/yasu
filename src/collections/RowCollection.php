<?php
namespace src\collections;

use ArrayObject;
use InvalidArgumentException;
use src\models\Row;

class RowCollection extends ArrayObject
{
    public function offsetSet($index, $row): void
    {
        if (!($row instanceof Row)) {
            throw new InvalidArgumentException("Input must be of type Row");
        }

        parent::offsetSet($index, $row);
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
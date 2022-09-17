<?php
namespace src\collections;

use ArrayObject;
use InvalidArgumentException;
use src\models\Block;

class BlockCollection extends ArrayObject
{
    public function offsetSet($index, $block): void
    {
        if (!($block instanceof Block)) {
            throw new InvalidArgumentException("Input must be of type Block");
        }

        parent::offsetSet($index, $block);
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
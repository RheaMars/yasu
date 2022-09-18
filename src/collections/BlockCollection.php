<?php
declare(strict_types=1);

namespace src\collections;

use ArrayObject;
use Exception;
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

    public function getBlockByPlayboardIndices(int $playboardRowIndex, int $playboardColIndex): Block
    {
        foreach ($this->getIterator() as $block) {
            if ($playboardRowIndex === $block->getPlayboardRowIndex() && $playboardColIndex === $block->getPlayboardColIndex()) {
                return $block;
            }
        }
        throw new Exception("Could not find block with indices (" . $playboardRowIndex . "," . $playboardColIndex . ") in block collection");
    }
}
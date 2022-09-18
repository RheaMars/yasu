<?php
declare(strict_types=1);

namespace src\iterators;

use src\models\Block;

class BlockIterator extends Iterator
{
    public function __construct(Block ...$blocks)
    {
        parent::__construct($blocks);
    }

    public function current(): Block
    {
        return parent::current();
    }

    public function offsetGet($offset): Block
    {
        return parent::offsetGet($offset);
    }

    public function merge(BlockIterator $other): BlockIterator
    {
        return new BlockIterator(
            ...array_merge(
                iterator_to_array($this),
                iterator_to_array($other)
            )
        );
    }

    public function sortByIndex(): void
    {
        $this->uasort(
            function(Block $a, Block $b) {
                return $a->getIndex() <=> $b->getIndex();
            }
        );
    }
}
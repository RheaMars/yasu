<?php
declare(strict_types=1);

namespace src\collections;

use ArrayIterator;
use src\models\Block;

class BlockCollection extends ArrayIterator
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

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    public function merge(BlockCollection $other): BlockCollection
    {
        return new BlockCollection(
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
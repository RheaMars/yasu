<?php
declare(strict_types=1);

namespace src\collections;

use ArrayIterator;
use src\models\Column;

class ColumnCollection extends ArrayIterator
{
    public function __construct(Column ...$columns)
    {
        parent::__construct($columns);
    }

    public function current(): Column
    {
        return parent::current();
    }

    public function offsetGet($offset): Column
    {
        return parent::offsetGet($offset);
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    public function merge(ColumnCollection $other): ColumnCollection
    {
        return new ColumnCollection(
            ...array_merge(
                iterator_to_array($this),
                iterator_to_array($other)
            )
        );
    }

    public function sortByIndex(): void
    {
        $this->uasort(
            function(Column $a, Column $b) {
                return $a->getIndex() <=> $b->getIndex();
            }
        );
    }
}
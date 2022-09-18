<?php
declare(strict_types=1);

namespace src\collections;

use ArrayIterator;
use src\models\Row;

class RowCollection extends ArrayIterator
{
    public function __construct(Row ...$rows)
    {
        parent::__construct($rows);
    }

    public function current(): Row
    {
        return parent::current();
    }

    public function offsetGet($offset): Row
    {
        return parent::offsetGet($offset);
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    public function merge(RowCollection $other): RowCollection
    {
        return new RowCollection(
            ...array_merge(
                iterator_to_array($this),
                iterator_to_array($other)
            )
        );
    }

    public function sortByIndex(): void
    {
        $this->uasort(
            function(Row $a, Row $b) {
                return $a->getIndex() <=> $b->getIndex();
            }
        );
    }
}
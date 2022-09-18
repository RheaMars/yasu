<?php
declare(strict_types=1);

namespace src\iterators;

use src\models\Row;

class RowIterator extends Iterator
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

    public function merge(RowIterator $other): RowIterator
    {
        return new RowIterator(
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
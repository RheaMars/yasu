<?php
declare(strict_types=1);

namespace src\iterators;

use src\models\Column;

class ColumnIterator extends Iterator
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

    public function merge(ColumnIterator $other): ColumnIterator
    {
        return new ColumnIterator(
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

    public function getColumnIndices(): array
    {
        $columnIndices = [];
        $columns = new ColumnIterator(...$this->getArrayCopy());
        foreach ($columns as $column){
            $columnIndices[] = $column->getIndex();
        }
        return $columnIndices;
    }
}
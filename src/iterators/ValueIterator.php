<?php
declare(strict_types=1);

namespace src\iterators;

class ValueIterator extends Iterator
{
    public function __construct(int ...$numbers)
    {
        parent::__construct($numbers);
    }

    public function current() : ?int
    {
        return parent::current();
    }

    public function offsetGet($offset) : ?int
    {
        return parent::offsetGet($offset);
    }

    public function sortByValue(): void
    {
        $this->uasort(
            function(int $a, int $b) {
                return $a <=> $b;
            }
        );
    }
}
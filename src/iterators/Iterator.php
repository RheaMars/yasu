<?php
namespace src\iterators;

use ArrayIterator;

/**
 * We use the ArrayIterator to guarantee type safety when using arrays of objects.
 * Beware: Nested loops over the ArrayIterator are critical and might lead to unexpected results.
 * If you need a nested loop create array copies of the ArrayIterator (e.g. $iterator->getArrayCopy()).
 */
abstract class Iterator extends ArrayIterator
{
    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}
<?php
declare(strict_types=1);

namespace src\iterators;

use src\models\PlayboardColumn;

class PlayboardColumnIterator extends Iterator
{
    public function __construct(PlayboardColumn ...$playboardColumns)
    {
        parent::__construct($playboardColumns);
    }

    public function current(): PlayboardColumn
    {
        return parent::current();
    }

    public function offsetGet($offset): PlayboardColumn
    {
        return parent::offsetGet($offset);
    }
}
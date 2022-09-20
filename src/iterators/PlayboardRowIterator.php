<?php
declare(strict_types=1);

namespace src\iterators;

use src\models\PlayboardRow;

class PlayboardRowIterator extends Iterator
{
    public function __construct(PlayboardRow ...$playboardRows)
    {
        parent::__construct($playboardRows);
    }

    public function current(): PlayboardRow
    {
        return parent::current();
    }

    public function offsetGet($offset): PlayboardRow
    {
        return parent::offsetGet($offset);
    }
}
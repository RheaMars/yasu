<?php
declare(strict_types=1);

namespace src\models;

use src\iterators\FieldIterator;

class PlayboardRow
{
    private int $playboardRowIndex;

    private FieldIterator $fields;

    public function __construct(int $playboardRowIndex)
    {
        $this->playboardRowIndex = $playboardRowIndex;
        $this->fields = new FieldIterator();
    }

    public function getPlayboardRowIndex(): int
    {
        return $this->playboardRowIndex;
    }

    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }
}
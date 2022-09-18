<?php
declare(strict_types=1);

namespace src\models;

use src\iterators\FieldIterator;

class PlayboardColumn
{
    private int $playboardColIndex;

    private FieldIterator $fields;

    public function __construct(int $playboardColIndex)
    {
        $this->playboardColIndex = $playboardColIndex;
        $this->fields = new FieldIterator();
    }

    public function getPlayboardColIndex(): int
    {
        return $this->playboardColIndex;
    }

    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }
}
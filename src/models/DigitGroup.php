<?php

namespace src\models;

use src\collections\FieldCollection;

class DigitGroup
{
    protected string $type;

    private int|string $index;

    private FieldCollection $fields;

    public function __construct(int|string $index)
    {
        $this->index = $index;
        $this->fields = new FieldCollection();
    }

    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }

    public function getFields(): FieldCollection
    {
        return $this->fields;
    }

    public function getIndex(): int|string
    {
        return $this->index;
    }

    public function getType(): string
    {
        return $this->type;
    }

}
<?php

namespace src\models;

class DigitGroup
{
    protected string $type;

    private int|string $index;

    /** @var Field[] */
    private array $fields = [];

    public function __construct(int|string $index)
    {
        $this->index = $index;
    }

    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }

    public function getFields(): array
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
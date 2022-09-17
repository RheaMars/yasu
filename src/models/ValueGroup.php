<?php

namespace src\models;

use src\collections\FieldCollection;

class ValueGroup
{
    protected string $type;

    private int|string $index;

    protected int $baseSize;

    private FieldCollection $fields;

    public function __construct(int|string $index, int $baseSize)
    {
        $this->index = $index;
        $this->fields = new FieldCollection();
        $this->baseSize = $baseSize;
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

    public function isValid(): bool
    {
        $fieldValues = [];

        foreach ($this->fields as $field) {
            $value = $field->getValue();
            if ($value !== null) {
                $fieldValues[] = $value;
            }
        }

        if (sizeof($fieldValues) != sizeof(array_unique($fieldValues))) {
            return false;
        }
        return true;
    }

}
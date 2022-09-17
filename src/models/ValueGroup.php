<?php

namespace src\models;

use src\collections\FieldCollection;
use src\collections\ValueCollection;

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
        if (0 === sizeof($this->getInvalidFields()->toArray())) {
            return true;
        }
        return false;
    }

    public function getInvalidFields(): FieldCollection
    {
        $values = $this->getValues()->toArray();

        $duplicateValues = array_unique(array_values(array_diff_assoc($values, array_unique($values))));

        $invalidFields = new FieldCollection();

        foreach ($this->fields as $field) {
            if (null !== $field->getValue()
                && !$field->isValueFixed()
                && in_array($field->getValue(), $duplicateValues)) {
                $invalidFields[] = $field;
            }
        }

        return $invalidFields;
    }

    private function getValues(): ValueCollection
    {
        return $this->fields->getValues();
    }
}
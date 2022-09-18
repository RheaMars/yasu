<?php
declare(strict_types=1);

namespace src\models;

use src\iterators\FieldIterator;
use src\iterators\ValueIterator;

abstract class ValueGroup
{
    protected string $type;

    private int|string $index;

    protected int $baseSize;

    private FieldIterator $fields;

    public function __construct(int|string $index, int $baseSize)
    {
        $this->index = $index;
        $this->fields = new FieldIterator();
        $this->baseSize = $baseSize;
    }

    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }

    public function getFields(): FieldIterator
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

    public function getInvalidFields(): FieldIterator
    {
        $values = $this->getValues()->toArray();

        $duplicateValues = array_unique(array_values(array_diff_assoc($values, array_unique($values))));

        $invalidFields = new FieldIterator();

        foreach ($this->fields as $field) {
            if (null !== $field->getValue()
                && !$field->isValueFixed()
                && in_array($field->getValue(), $duplicateValues)) {
                $invalidFields[] = $field;
            }
        }

        return $invalidFields;
    }

    private function getValues(): ValueIterator
    {
        return $this->fields->getValues();
    }
}
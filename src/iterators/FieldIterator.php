<?php
declare(strict_types=1);

namespace src\iterators;

use src\models\Field;

class FieldIterator extends Iterator
{
    public function __construct(Field ...$fields)
    {
        parent::__construct($fields);
    }

    public function current(): Field
    {
        return parent::current();
    }

    public function offsetGet($offset): Field
    {
        return parent::offsetGet($offset);
    }

    public function merge(FieldIterator $other): FieldIterator
    {
        return new FieldIterator(
            ...array_merge(
                iterator_to_array($this),
                iterator_to_array($other)
            )
        );
    }

    public function getValues(bool $withEmptyFields = true): ValueIterator
    {
        $values = new ValueIterator();
        foreach ($this->getArrayCopy() as $field) {
            if (!$withEmptyFields && null === $field->getValue()) {
                continue;
            }
            $values[] = $field->getValue();
        }
        return $values;
    }

    public function setNonEmptyFieldsToFixed(): void
    {
        $fields = new FieldIterator(...$this->getArrayCopy());
        foreach ($fields as $field) {
            if (null !== $field->getValue()) {
                $field->setToFixed();
            }
        }
    }

    public function emptyNonFixedFields(): void
    {
        $fields = new FieldIterator(...$this->getArrayCopy());
        foreach ($fields as $field) {
            if (!$field->isValueFixed()) {
                $field->setValue(null);
            }
        }
    }

    public function emptyValues(): void
    {
        $fields = new FieldIterator(...$this->getArrayCopy());
        foreach ($fields as $field) {
            $field->setValue(null);
        }
    }

    public function prefillRandomly(int $size): void
    {
        $fields = new FieldIterator(...$this->getArrayCopy());
        foreach ($fields as $field) {
            $field->setValue(rand(1, pow($size, 2)));
        }
    }

    public function allValuesSet(): bool
    {
        $fields = new FieldIterator(...$this->getArrayCopy());
        foreach ($fields as $field) {
            if (null === $field->getValue()) {
                return false;
            }
        }
        return true;
    }

    public static function mergeAll(array $fieldIterators): FieldIterator
    {
        $flattened = [];
        foreach ($fieldIterators as $fields) {
            foreach ($fields as $field) {
                $flattened[] = $field;
            }
        }
        return new FieldIterator(...$flattened);
    }
}
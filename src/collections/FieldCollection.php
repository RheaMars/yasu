<?php
declare(strict_types=1);

namespace src\collections;

use ArrayIterator;
use src\models\Field;

class FieldCollection extends ArrayIterator
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

    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    public function merge(FieldCollection $other): FieldCollection
    {
        return new FieldCollection(
            ...array_merge(
                iterator_to_array($this),
                iterator_to_array($other)
            )
        );
    }

    public function getValues(): ValueCollection
    {
        $values = new ValueCollection();
        foreach ($this->getArrayCopy() as $field) {
            $values[] = $field->getValue();
        }
        return $values;
    }

    public function setNonEmptyFieldsToFixed(): void
    {
        foreach ($this->getArrayCopy() as $field) {
            if (null !== $field->getValue()) {
                $field->setToFixed();
            }
        }
    }

    public function emptyValues(): void
    {
        foreach ($this->getArrayCopy() as $field) {
            $field->setValue(null);
        }
    }

    public function prefillRandomly(int $size): void
    {
        foreach ($this->getArrayCopy() as $field) {
            $field->setValue(rand(1, pow($size, 2)));
        }
    }

    public function allValuesSet(): bool
    {
        foreach ($this->getArrayCopy() as $field) {
            if (null === $field->getValue()) {
                return false;
            }
        }
        return true;
    }

    public static function mergeAll(array $fieldCollections): FieldCollection
    {
        $flattened = [];
        foreach ($fieldCollections as $fields) {
            foreach ($fields as $field) {
                $flattened[] = $field;
            }
        }
        return new FieldCollection(...$flattened);
    }
}
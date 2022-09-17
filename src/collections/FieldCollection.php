<?php
namespace src\collections;

use ArrayObject;
use InvalidArgumentException;
use src\models\Field;

class FieldCollection extends ArrayObject
{
    public function offsetSet($index, $field): void
    {
        if (!($field instanceof Field)) {
            throw new InvalidArgumentException("Input must be of type Field");
        }

        parent::offsetSet($index, $field);
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    public static function merge(array $fieldCollections): FieldCollection
    {
        $flattened = [];
        foreach ($fieldCollections as $fields) {
            foreach ($fields as $field) {
                $flattened[] = $field;
            }
        }
        return new FieldCollection($flattened);
    }

    public function getDigitValues(): IntegerCollection
    {
        $integerCollection = new IntegerCollection();
        foreach ($this->getIterator() as $field) {
            $integerCollection[] = $field->getDigit()->getValue();
        }
        return $integerCollection;
    }
}
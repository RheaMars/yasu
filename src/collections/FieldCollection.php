<?php
namespace src\collections;

use ArrayObject;
use Exception;
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

    public function getValues(): IntegerCollection
    {
        $integerCollection = new IntegerCollection();
        foreach ($this->getIterator() as $field) {
            $integerCollection[] = $field->getValue();
        }
        return $integerCollection;
    }

    public function getFieldByIndices(int $rowIndex, int $colIndex): Field
    {
        foreach ($this->getIterator() as $field) {
            if ($rowIndex === $field->getRowIndex() && $colIndex === $field->getColIndex()) {
                return $field;
            }
        }

        throw new Exception("No field in given field collection has indices " . $rowIndex . "-" . $colIndex);
    }

    public function setNonEmptyFieldsToFixed(): void
    {
        foreach ($this->getIterator() as $field) {
            if (null !== $field->getValue()) {
                $field->setToFixed();
            }
        }
    }

    public function emptyValues(): void
    {
        foreach ($this->getIterator() as $field) {
            $field->setValue(null);
        }
    }

    public function prefillRandomly(int $size): void
    {
        foreach ($this->getIterator() as $field) {
            $field->setValue(rand(1, pow($size, 2)));
        }
    }

}
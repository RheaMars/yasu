<?php
namespace src\collections;

use ArrayObject;
use Exception;
use InvalidArgumentException;
use src\models\Digit;
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
            if (null !== $field->getDigit()->getValue()) {
                $field->setToFixed();
            }
        }
    }

    public function emptyDigitValues(): void
    {
        foreach ($this->getIterator() as $field) {
            $field->setDigit(new Digit(null));
        }
    }

    public function prefillRandomly(int $size): void
    {
        foreach ($this->getIterator() as $field) {
            $field->setDigit(Digit::getRandomDigit($size));
        }
    }

}
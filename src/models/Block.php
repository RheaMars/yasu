<?php
declare(strict_types=1);

namespace src\models;

use Exception;
use src\collections\FieldCollection;

class Block extends ValueGroup
{
    private int $playboardRowIndex;

    private int $playboardColIndex;

    public function __construct(string $index, int $playboardRowIndex, int $playboardColIndex, int $baseSize)
    {
        parent::__construct($index, $baseSize);
        $this->type = "block";

        $this->playboardRowIndex = $playboardRowIndex;
        $this->playboardColIndex = $playboardColIndex;
    }

    public function getPlayboardRowIndex(): int
    {
        return $this->playboardRowIndex;
    }

    public function getPlayboardColIndex(): int
    {
        return $this->playboardColIndex;
    }

    public function getFieldsFromBlockRow(int $blockRowIndex): FieldCollection
    {
        $fields = new FieldCollection();
        foreach ($this->getFields() as $field){
            if ($blockRowIndex === $field->getBlockRowIndex()){
                $fields[] = $field;
            }
        }
        return $fields;
    }

    public function getFieldsFromBlockColumn(int $blockColIndex): FieldCollection
    {
        $fields = new FieldCollection();
        foreach ($this->getFields() as $field){
            if ($blockColIndex === $field->getBlockColIndex()){
                $fields[] = $field;
            }
        }
        return $fields;
    }

    public function getFieldFromBlockIndices(int $blockRowIndex, int $blockColIndex): ?Field
    {
        foreach ($this->getFields() as $field){
            if ($blockRowIndex === $field->getBlockRowIndex() && $blockColIndex === $field->getBlockColIndex()){
                return $field;
            }
        }
        return null;
    }

    /**
     * Prefill from matrix rows or columns.
     */
    public function prefillFromMatrix(array $matrix): void
    {
        if (count($matrix) != $this->baseSize) {
            throw new Exception("Can't prefill from matrix - wrong dimensions");
        }

        for ($i = 1; $i <= $this->baseSize; $i++) {
            for ($j = 1; $j <= $this->baseSize; $j++) {
                $field = $this->getFieldFromBlockIndices($i, $j);
                $field->setValue($matrix[$i - 1][$j - 1]);
            }
        }
    }

    public function getAsMatrixRows(): array
    {
        $matrixRows = [];
        for ($i = 1; $i <= $this->baseSize; $i++) {
            $matrixRows[] = $this->getFieldsFromBlockRow($i);
        }
        return $matrixRows;
    }

    public function getAsMatrixColumns(): array
    {
        $matrixCols = [];
        for ($i = 1; $i <= $this->baseSize; $i++) {
            $matrixCols[] = $this->getFieldsFromBlockColumn($i);
        }
        return $matrixCols;
    }
}
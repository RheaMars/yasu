<?php

namespace src\models;

use src\collections\FieldCollection;

class Block extends DigitGroup
{
    private int $playboardRowIndex;

    private int $playboardColIndex;

    public function __construct(string $index, int $playboardRowIndex, int $playboardColIndex)
    {
        parent::__construct($index);
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

    public function getFieldFromBlockCoordinates(int $blockRowIndex, int $blockColIndex): ?Field
    {
        foreach ($this->getFields() as $field){
            if ($blockRowIndex === $field->getBlockRowIndex() && $blockColIndex === $field->getBlockColIndex()){
                return $field;
            }
        }
        return null;
    }
}
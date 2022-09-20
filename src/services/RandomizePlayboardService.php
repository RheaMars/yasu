<?php
declare(strict_types=1);

namespace src\services;

use src\models\Playboard;
use src\models\PlayboardRow;
use src\models\PlayboardColumn;


class RandomizePlayboardService
{
    private array $legalValues;

    private Playboard $playboard;

    public function __construct(Playboard $playboard)
    {
        $this->playboard = $playboard;
        $this->legalValues = range(1, pow($this->playboard->getBaseSize(), 2));
    }

    public function permuteRowsWithinPlayboardRows(): void
    {
        $playboardRows = $this->playboard->getPlayboardRows();
        foreach ($playboardRows as $playboardRow){
            $this->permuteRowsWithinPlayboardRow($playboardRow);
        }
    }

    private function permuteRowsWithinPlayboardRow(PlayboardRow $playboardRow): void
    {
        $rows = $this->playboard->getRowsByPlayboardRowIndex($playboardRow->getPlayboardRowIndex());
        $rowIndices = $rows->getRowIndices();
        $permutationRows = [];
        foreach ($rows as $row){
            // deep copy the rows to permute
            $permutationRows[] = unserialize(serialize($row));
        }
        shuffle($permutationRows);

        foreach ($rowIndices as $rowIndex){
            $permutationRow = array_shift($permutationRows);
            $this->playboard->getRowByIndex($rowIndex)->replaceValuesbyRow($permutationRow);
        }
    }

    public function permuteColumnsWithinPlayboardColumns(): void
    {
        $playboardColumns = $this->playboard->getPlayboardColumns();
        foreach ($playboardColumns as $playboardColumn){
            $this->permuteColumnsWithinPlayboardColumn($playboardColumn);
        }
    }

    private function permuteColumnsWithinPlayboardColumn(PlayboardColumn $playboardColumn): void
    {
        $columns = $this->playboard->getColumnsByPlayboardColumnIndex($playboardColumn->getPlayboardColumnIndex());
        $columnIndices = $columns->getColumnIndices();
        $permutationColumns = [];
        foreach ($columns as $column){
            // deep copy the columns to permute
            $permutationColumns[] = unserialize(serialize($column));
        }
        shuffle($permutationColumns);

        foreach ($columnIndices as $columnIndex){
            $permutationColumn = array_shift($permutationColumns);
            $this->playboard->getColumnByIndex($columnIndex)->replaceValuesbyColumn($permutationColumn);
        }
    }

    public function permuteValues(): void
    {
        $shuffledValues = $this->legalValues;
        shuffle($shuffledValues);
//        echo "Shuffled values:<br>";
//        var_dump($shuffledValues);

        foreach ($this->playboard->getFields() as $field) {
            $value = $field->getValue();
            $shuffleValue = $shuffledValues[$value - 1];
            $field->setValue($shuffleValue);
            //echo "Set value of field " . $field->getIndex() . " from " . $value . " to " . $shuffleValue . "<br>";
        }
    }

    // public function permutePlayboardRows(PlayboardRow $playboardRow): void
    // {
    //     ...
    // }

    // public function permutePlayboardColumns(PlayboardRow $playboardRow): void
    // {
    //     ...
    // }

    // public function rotatePlayboard(): void
    // {
    //     ...
    // }
}
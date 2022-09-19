<?php
declare(strict_types=1);

namespace src\services;

use src\models\Playboard;
use src\models\PlayboardRow;
use src\models\PlayboardColumn;


class RandomizePlayboardService
{
    private Playboard $playboard;

    public function __construct(Playboard $playboard)
    {
        $this->playboard = $playboard;
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
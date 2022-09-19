<?php
declare(strict_types=1);

namespace src\services;

use src\models\Playboard;
use src\models\PlayboardRow;

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

    // public function permutePlayboardRows(PlayboardRow $playboardRow): void
    // {
    //     ...
    // }
    // public function permuteColumnsWithinPlayboardColumn(PlayboardColumn $playboardCol): void
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
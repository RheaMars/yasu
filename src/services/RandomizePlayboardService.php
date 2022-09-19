<?php
declare(strict_types=1);

namespace src\services;

use src\models\Playboard;
use src\models\PlayboardRow;

class RandomizePlayboardService
{
    public function permuteRowsWithinPlayboardRows(Playboard $playboard): void
    {
        $playboardRows = $playboard->getPlayboardRows();
        foreach ($playboardRows as $playboardRow){
            $this->permuteRowsWithinPlayboardRow($playboard, $playboardRow);
        }
    }

    private function permuteRowsWithinPlayboardRow(Playboard $playboard, PlayboardRow $playboardRow): void
    {
        $rows = $playboard->getRowsByPlayboardRowIndex($playboardRow->getPlayboardRowIndex());
        $rowIndices = $rows->getRowIndices();
        $permutationRows = [];
        foreach ($rows as $row){
            // deep copy the rows to permute
            $permutationRows[] = unserialize(serialize($row));
        }
        shuffle($permutationRows);

        foreach ($rowIndices as $rowIndex){
            $permutationRow = array_shift($permutationRows);
            $playboard->getRowByIndex($rowIndex)->replaceValuesbyRow($permutationRow);
        }
    }
}
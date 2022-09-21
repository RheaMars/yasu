<?php
declare(strict_types=1);

namespace src\services;

use Exception;
use src\models\Block;
use src\models\Field;
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
        $permutations = $this->splitIntoRandomSubArrays($shuffledValues);

        foreach ($this->playboard->getFields() as $field) {
            $value = $field->getValue();
            foreach ($permutations as $permutation) {

                $index = array_search($value, $permutation);
                if (false === $index){
                    continue;
                }
                $shuffleValue = $index === (sizeof($permutation) - 1) ? $permutation[0] : $permutation[$index + 1];
                break;
            }

            $field->setValue($shuffleValue);
            echo "Set value of field " . $field->getIndex() . " from " . $value . " to " . $shuffleValue . "<br>";
        }
    }

    private function splitIntoRandomSubArrays(array $values): array
    {
        $subarrays = [];
        $maxLength = sizeof($values);
        $sliceOffset = 0;
        while ($maxLength > 0){
            echo "maxLength: $maxLength <br>";
            $randomLength = rand(1, $maxLength);
            echo "randomLength: $randomLength <br>";
            echo "sliceOffset: $sliceOffset <br>";
            $subarrays[] = array_slice($values, $sliceOffset, $randomLength);
            $maxLength -= $randomLength;
            $sliceOffset += $randomLength;
        }

        return $subarrays;
    }

    // public function permutePlayboardRows(PlayboardRow $playboardRow): void
    // {
    //     ...
    // }

    // public function permutePlayboardColumns(PlayboardRow $playboardRow): void
    // {
    //     ...
    // }


    public function permuteByRotatingBlocks(): void
    {
        $turns = 1;
        foreach ($this->playboard->getBlocks() as $block) {
            $this->rotateBlockByNumberOfFields($block, -1);
            break;
        }
    }

    /**
     * Rotates the given block counterclockwise by the given number of turns.
     * (it rotates the block in a clockwise manner when number of turns is negative).
     * By 'number of turns', we are referring to the outer ring of the block.
     */
    private function rotateBlockByNumberOfFields(Block $block, int $turns): void
    {
        $baseSize = $this->playboard->getBaseSize();

        // determine the actual number of turns for the outer ring in a counterclockwise manner
        $sizeOfOuterRing = $baseSize === 1? 1: ($baseSize - 1) * 4;
        $turnsModBaseSize = $turns % $sizeOfOuterRing;
        $turns = $turnsModBaseSize < 0? ($sizeOfOuterRing + $turnsModBaseSize): $turnsModBaseSize;
        echo "TURNS: $turns<br>";
        if (0 === $turns) {
            return;
        }

        $matrix = unserialize(serialize($block->getAsMatrixRows()));

        foreach ($block->getFields() as $field) {
            $currentBlockCoordinates = $this->getBlockCoordinates($field, $baseSize);
            $distance = $this->getDistanceFromBlockCenter($field, $baseSize);
            for ($i = 0; $i < $turns; $i++){
                $nextBlockCoordinates = $this->getNextBlockCoordinates($currentBlockCoordinates, $distance);
                $currentBlockCoordinates = $nextBlockCoordinates;
            }
            $nextBlockRowIndex = $this->getBlockIndicesFromBlockCoordinates($nextBlockCoordinates, $baseSize)[0];
            $nextBlockColIndex = $this->getBlockIndicesFromBlockCoordinates($nextBlockCoordinates, $baseSize)[1];

            $nextField = $matrix[$nextBlockRowIndex - 1][$nextBlockColIndex - 1];
            $field->setValue($nextField->getValue());
        }
    }

    /**
     * Returns the coordinates of a field with respect to its block center.
     */
    private function getBlockCoordinates(Field $field, int $baseSize): array
    {
        $center = ($baseSize - 1) / 2;

        return [$field->getBlockRowIndex() - 1 - $center, $center - ($field->getBlockColIndex() - 1)];
    }

    private function getBlockIndicesFromBlockCoordinates(array $blockCoordinates, int $baseSize): array
    {
        $xCoordinate = $blockCoordinates[0];
        $yCoordinate = $blockCoordinates[1];

        $center = ($baseSize - 1) / 2;
        $blockRowIndex = $xCoordinate + 1 + $center;
        $blockColIndex = 1 + $center - $yCoordinate;
        return [$blockRowIndex, $blockColIndex];
    }

    private function getNextBlockCoordinates(array $coordinates, int|float $distance): array
    {
        $xCoordinate = $coordinates[0];
        $yCoordinate = $coordinates[1];

        if ($distance === 0) {
            return $coordinates;
        }

        // move down
        if (($xCoordinate === (-1 * $distance)) && ($yCoordinate <= $distance) && ($yCoordinate > (-1 * $distance))) {
            $nextXCoordinate = $xCoordinate;
            $nextYCoordinate = $yCoordinate - 1;
            }
        // move to the right
        else if (($yCoordinate === (-1 * $distance)) && ($xCoordinate >= (-1 * $distance)) && ($xCoordinate < $distance)) {
            $nextXCoordinate = $xCoordinate + 1;
            $nextYCoordinate = $yCoordinate;
            }
        // move up
        else if (($xCoordinate === $distance) && ($yCoordinate >= (-1 * $distance)) && ($yCoordinate < $distance)) {
            $nextXCoordinate = $xCoordinate;
            $nextYCoordinate = $yCoordinate + 1;
            }
        // move to the left
        else if (($yCoordinate === $distance) && ($xCoordinate <= $distance) && ($xCoordinate > (-1 * $distance))) {
            $nextXCoordinate = $xCoordinate - 1;
            $nextYCoordinate = $yCoordinate;
            }
        else {
            throw new Exception("Given coordinates ($xCoordinate, $yCoordinate) don't seem to agree with the given distance $distance");
        }
        $nextCoordinates = [$nextXCoordinate, $nextYCoordinate];

        return $nextCoordinates;
    }

    private function getDistanceFromBlockCenter(Field $field, int $baseSize): int|float
    {
        $center = ($baseSize - 1) / 2;
        $fieldMatrixRowIndex = $field->getBlockRowIndex() - 1;
        $fieldMatrixColumnIndex = $field->getBlockColIndex() - 1;

        return max([abs($fieldMatrixRowIndex - $center), abs($fieldMatrixColumnIndex - $center)]);
    }
}
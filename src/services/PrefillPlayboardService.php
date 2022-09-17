<?php

namespace src\services;

use src\collections\FieldCollection;
use src\collections\ValueCollection;
use src\models\Block;
use src\models\Column;
use src\models\Playboard;
use src\models\Row;

class PrefillPlayboardService
{
    public function prefillRandomly(Playboard $playboard): void
    {
        $playboard->getFields()->prefillRandomly($playboard->getBaseSize());
    }

    public function prefillByBlocksDiagonally(Playboard $playboard, int $maxRounds): void
    {
        $values = range(1, pow($playboard->getBaseSize(), 2));

        $sortedBlockIndices = $this->getBlockIndicesSortedDiagonally($playboard);

        $counter = 0;
        while ($counter < $maxRounds && !($playboard->isValid() && $playboard->isComplete())) {
            $counter++;
            $playboard->emptyFieldsByPercentage(1.0);
            shuffle($values);

            foreach ($values as $value) {
                foreach ($sortedBlockIndices as $blockIndex) {

                    $block = $playboard->getBlocks()[$blockIndex["row"] . "-" . $blockIndex["col"]];

                    $blockFields = $block->getFields()->toArray();
                    shuffle($blockFields);

                    foreach ($blockFields as $field) {

                        if (null !== $field->getValue()) {
                            continue;
                        }

                        $field->setValue($value);

                        /** @var $row Row */
                        $row = $playboard->getRows()[$field->getRowIndex()];
                        /** @var $col Column */
                        $col = $playboard->getColumns()[$field->getColIndex()];
                        /** @var $block Block */
                        $block = $playboard->getBlocks()[$field->getBlockIndex()];

                        if ($row->isValid() && $col->isValid() && $block->isValid()) {
                            break;
                        }
                        $field->setValue(null);
                    }

                    // if field could not be filled, the playboard is invalid - try again
                    if (null === $field->getValue()) {
                        break 2;
                    }
                }
            }
        }
    }

    private function getBlockIndicesSortedDiagonally(Playboard $playboard): array
    {
        $indices = [];
        foreach ($playboard->getBlocks() as $block) {
            $indices[] = [
                "row" => $block->getPlayboardRowIndex(),
                "col" => $block->getPlayboardColIndex(),
                "sum" => $block->getPlayboardRowIndex() + $block->getPlayboardColIndex()
            ];
        }

        $indicesSum = array_column($indices, 'sum');
        array_multisort($indicesSum, SORT_ASC, $indices);

        return $indices;
    }

    /**
     * Note: This approach doesn't give good results! We leave it here for test purposes.
     */
    public function prefillByRows(Playboard $playboard, int $maxRounds): void
    {

        $values = range(1, pow($playboard->getBaseSize(), 2));

        $counter = 0;
        while ($counter < $maxRounds && !($playboard->isValid() && $playboard->isComplete())) {
            $counter++;
            $playboard->emptyFieldsByPercentage(1.0);
            shuffle($values);

            foreach ($playboard->getFields() as $field) {
                $rowIndex = $field->getRowIndex();
                $colIndex = $field->getColIndex();
                $blockIndex = $field->getBlockIndex();
                foreach ($values as $value) {
                    $field->setValue($value);

                    /** @var $row Row */
                    $row = $playboard->getRows()[$rowIndex];
                    /** @var $col Column */
                    $col = $playboard->getColumns()[$colIndex];
                    /** @var $block Block */
                    $block = $playboard->getBlocks()[$blockIndex];

                    if ($row->isValid() && $col->isValid() && $block->isValid()) {
                        break;
                    }
                    $field->setValue(null);
                }

                // if field could not be filled, the playboard is invalid - try again
                if (null === $field->getValue()) {
                    break;
                }
            }
        }
    }

    public function prefillByPlayboardRows(Playboard $playboard, int $maxRounds): void
    {
        $values = range(1, pow($playboard->getBaseSize(), 2));

        $counter = 0;
        while ($counter < $maxRounds && !($playboard->isValid() && $playboard->isComplete())) {
            $counter++;
            $playboard->emptyFieldsByPercentage(1.0);
            shuffle($values);

            foreach ($values as $value) {
                foreach ($playboard->getBlocks() as $block) {

                    $blockFields = $block->getFields()->toArray();
                    shuffle($blockFields);

                    foreach ($blockFields as $field) {

                        if (null !== $field->getValue()) {
                            continue;
                        }

                        $field->setValue($value);

                        /** @var $row Row */
                        $row = $playboard->getRows()[$field->getRowIndex()];
                        /** @var $col Column */
                        $col = $playboard->getColumns()[$field->getColIndex()];
                        /** @var $block Block */
                        $block = $playboard->getBlocks()[$field->getBlockIndex()];

                        if ($row->isValid() && $col->isValid() && $block->isValid()) {
                            break;
                        }

                        $field->setValue(null);
                    }

                    // if field could not be filled, the playboard is invalid - try again
                    if (null === $field->getValue()) {
                        break 2;
                    }
                }
            }
        }
    }

    /**
     * This prefills the fields of the first block in a shuffled manner
     * and then fills the next blocks based on a "parent block" (left or upper),
     * by permuting the block rows or columns of the latter.
     * It is a non-brute-force method to prefill fields.
     */
    public function prefillByPermutations(Playboard $playboard): void
    {
        foreach ($playboard->getBlocks() as $block) {

            /** @var $block Block */

            $parentBlock = $this->getParentBlock($playboard, $block);

            // prefill fields of first block with randomly shuffled values
            if (null === $parentBlock) {
                $values = range(1, pow($playboard->getBaseSize(), 2));
                shuffle($values);
                $valueUnits = $this->createUnitMatrices(new ValueCollection($values), $playboard->getBaseSize())["rowUnits"];
                $block->prefillFromMatrix($valueUnits);
            }
            // prefill from left parent
            else if ($parentBlock->getPlayboardRowIndex() === $block->getPlayboardRowIndex()) {
                $parentPermutationUnits = $parentBlock->getAsMatrixRows();
                $block->prefillFromMatrix($this->getPermutedUnits($playboard, $parentPermutationUnits)["rowUnits"]);
            }
            // prefill from upper parent
            else {
                $parentPermutationUnits = $parentBlock->getAsMatrixColumns();
                $block->prefillFromMatrix($this->getPermutedUnits($playboard, $parentPermutationUnits)["colUnits"]);
            }
        }
    }

    private function getParentBlock(Playboard $playboard, Block $block): ?Block
    {
        if (1 === $block->getPlayboardRowIndex() && 1 === $block->getPlayboardColIndex()) {
            return null;
        }
        if (1 < $block->getPlayboardColIndex()) {
            return $playboard->getBlocks()->getBlockByPlayboardIndices($block->getPlayboardRowIndex(), $block->getPlayboardColIndex() - 1);
        }
        if (1 < $block->getPlayboardRowIndex()) {
            return $playboard->getBlocks()->getBlockByPlayboardIndices($block->getPlayboardRowIndex() - 1, $block->getPlayboardColIndex());
        }
        return null;
    }

    /**
     * This returns an associative array of length 2,
     * where the first component "rowUnits" has an array of "row units" as a value,
     * and the second component "colUnits" has an array of "column units" as a value.
     * In other words, this arranges the given values as matrix rows (first output)
     * as well as matrix columns (second output).
     *
     * Example:
     * Input: [1, 2, 3, 4, 5, 6, 7, 8, 9]
     * Output: ["rowUnits" => [[1, 2, 3], [4, 5, 6], [7, 8, 9]], "colUnits" => [[1, 4, 7], [2, 5, 8], [3, 6, 9]]]
     */
    private function createUnitMatrices(ValueCollection $values, int $baseSize): array
    {
        $rowUnits = array_chunk($values->toArray(), $baseSize);
        $colUnits = [];
        for ($i = 0; $i < $baseSize; $i++) {
            $colUnits[$i] = [];
            foreach ($rowUnits as $row) {
                $colUnits[$i] = array_merge($colUnits[$i], [$row[$i]]);
            }
        }

        return ["rowUnits" => $rowUnits, "colUnits" => $colUnits];
    }

    private function getPermutedUnits(Playboard $playboard, array $parentPermutationUnits): array
    {
        $permutedUnits = $this->getNextCyclicPermutation($parentPermutationUnits);
        $fields = FieldCollection::merge($permutedUnits);

        return $this->createUnitMatrices($fields->getValues(), $playboard->getBaseSize());
    }

    private function getNextCyclicPermutation(array $permutationUnits): array
    {
        $headUnit = array_shift($permutationUnits);
        return array_merge($permutationUnits, [$headUnit]);
    }
}
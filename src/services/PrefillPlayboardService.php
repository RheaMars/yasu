<?php
declare(strict_types=1);

namespace src\services;

use src\iterators\FieldIterator;
use src\iterators\ValueIterator;
use src\models\Block;
use src\models\Playboard;

class PrefillPlayboardService
{
    private array $legalValues;

    private int $maxRounds;

    private Playboard $playboard;

    public function __construct(Playboard $playboard, int $maxRounds = 1000)
    {
        $this->playboard = $playboard;
        $this->maxRounds = $maxRounds ?? 100 * pow($this->playboard->getBaseSize(), 2);
        $this->legalValues = range(1, pow($this->playboard->getBaseSize(), 2));
    }

    public function prefillRandomly(): void
    {
        $this->playboard->getFields()->prefillRandomly($this->playboard->getBaseSize());
    }

    public function prefillByBlocksDiagonally(): void
    {
        $sortedBlockIndices = $this->getBlockIndicesSortedDiagonally();

        $counter = 0;
        while ($counter < $this->maxRounds && !($this->playboard->isValid() && $this->playboard->isComplete())) {

            $counter++;
            $this->playboard->emptyFieldsByPercentage(1.0);
            shuffle($this->legalValues);

            foreach ($this->legalValues as $legalValue) {

                foreach ($sortedBlockIndices as $blockIndex) {

                    $block = $this->playboard->getBlocks()[$blockIndex["row"] . "-" . $blockIndex["col"]];

                    $blockFields = $block->getFields()->toArray();
                    shuffle($blockFields);

                    $legalValueSetInBlock = false;

                    foreach ($blockFields as $field) {

                        if (null !== $field->getValue()) {
                            continue;
                        }

                        $field->setValue($legalValue);

                        $row = $this->playboard->getRows()[$field->getRowIndex()];
                        $col = $this->playboard->getColumns()[$field->getColIndex()];
                        $block = $this->playboard->getBlocks()[$field->getBlockIndex()];

                        if ($row->isValid() && $col->isValid() && $block->isValid()) {
                            $legalValueSetInBlock = true;
                            break;
                        }
                        $field->setValue(null);
                    }

                    if (!$legalValueSetInBlock) {
                        break 2; // start next round
                    }
                }
            }
        }
    }

    private function getBlockIndicesSortedDiagonally(): array
    {
        $indices = [];
        foreach ($this->playboard->getBlocks() as $block) {
            $indices[] = [
                "row" => $block->getPlayboardRowIndex(),
                "col" => $block->getPlayboardColumnIndex(),
                "sum" => $block->getPlayboardRowIndex() + $block->getPlayboardColumnIndex()
            ];
        }

        $indicesSum = array_column($indices, 'sum');
        array_multisort($indicesSum, SORT_ASC, $indices);

        return $indices;
    }

    /**
     * Note: This approach doesn't give good results! We leave it here for test purposes.
     */
    public function prefillByRows(): void
    {
        $counter = 0;
        while ($counter < $this->maxRounds && !($this->playboard->isValid() && $this->playboard->isComplete())) {
            $counter++;
            $this->playboard->emptyFieldsByPercentage(1.0);
            shuffle($this->legalValues);

            foreach ($this->playboard->getFields() as $field) {
                $rowIndex = $field->getRowIndex();
                $colIndex = $field->getColIndex();
                $blockIndex = $field->getBlockIndex();

                $fieldValueIsSet = false;

                foreach ($this->legalValues as $legalValue) {
                    $field->setValue($legalValue);

                    $row = $this->playboard->getRows()[$rowIndex];
                    $col = $this->playboard->getColumns()[$colIndex];
                    $block = $this->playboard->getBlocks()[$blockIndex];

                    if ($row->isValid() && $col->isValid() && $block->isValid()) {
                        $fieldValueIsSet = true;
                        break;
                    }
                    $field->setValue(null);
                }

                if (!$fieldValueIsSet) {
                    break; // start next round
                }
            }
        }
    }

    public function prefillByPlayboardRows(): void
    {
        $counter = 0;
        while ($counter < $this->maxRounds && !($this->playboard->isValid() && $this->playboard->isComplete())) {
            $counter++;
            $this->playboard->emptyFieldsByPercentage(1.0);
            shuffle($this->legalValues);

            foreach ($this->legalValues as $legalValue) {

                foreach ($this->playboard->getBlocks() as $block) {

                    $blockFields = $block->getFields()->toArray();
                    shuffle($blockFields);

                    $legalValueSetInBlock = false;

                    foreach ($blockFields as $field) {

                        if (null !== $field->getValue()) {
                            continue;
                        }

                        $field->setValue($legalValue);

                        $row = $this->playboard->getRows()[$field->getRowIndex()];
                        $col = $this->playboard->getColumns()[$field->getColIndex()];
                        $block = $this->playboard->getBlocks()[$field->getBlockIndex()];

                        if ($row->isValid() && $col->isValid() && $block->isValid()) {
                            $legalValueSetInBlock = true;
                            break;
                        }

                        $field->setValue(null);
                    }

                    if (!$legalValueSetInBlock) {
                        break 2; // start next round
                    }
                }
            }
        }
    }

    /**
     * This prefills the fields of the first block in a shuffled manner
     * and then fills the next blocks based on a "parent block" (left or upper),
     * by permuting the block rows or columns of the latter.
     * It is a non-brute-force method to prefill fields in one round.
     */
    public function prefillByPermutations(): void
    {
        foreach ($this->playboard->getBlocks() as $block) {

            $parentBlock = $this->getParentBlock($block);

            // prefill fields of first block with randomly shuffled values
            if (null === $parentBlock) {
                shuffle($this->legalValues);
                $valueUnits = $this->createUnitMatrices(new ValueIterator(...$this->legalValues))["rowUnits"];
                $block->prefillFromMatrix($valueUnits);
            }
            // prefill from left parent
            else if ($parentBlock->getPlayboardRowIndex() === $block->getPlayboardRowIndex()) {
                $parentPermutationUnits = $parentBlock->getAsMatrixRows();
                $block->prefillFromMatrix($this->getPermutedUnits($parentPermutationUnits)["rowUnits"]);
            }
            // prefill from upper parent
            else {
                $parentPermutationUnits = $parentBlock->getAsMatrixColumns();
                $block->prefillFromMatrix($this->getPermutedUnits($parentPermutationUnits)["colUnits"]);
            }
        }
    }

    private function getParentBlock(Block $block): ?Block
    {
        if (1 === $block->getPlayboardRowIndex() && 1 === $block->getPlayboardColumnIndex()) {
            return null;
        }
        if (1 < $block->getPlayboardColumnIndex()) {
            return $this->playboard->getBlocks()[$block->getPlayboardRowIndex() . "-" . ($block->getPlayboardColumnIndex() - 1)];
        }
        if (1 < $block->getPlayboardRowIndex()) {
            return $this->playboard->getBlocks()[($block->getPlayboardRowIndex() - 1) . "-" . $block->getPlayboardColumnIndex()];
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
    private function createUnitMatrices(ValueIterator $values): array
    {
        $baseSize = $this->playboard->getBaseSize();
        $rowUnits = array_chunk($values->toArray(), $baseSize);
        $colUnits = [];
        for ($i = 0; $i < $baseSize; $i++) {
            $colUnits[$i] = [];
            foreach ($rowUnits as $row) {
                $colUnits[$i] = array_merge($colUnits[$i], [$row[$i]]);
            }
        }

        //TODO Consider to refactor to custom Matrix type
        return ["rowUnits" => $rowUnits, "colUnits" => $colUnits];
    }

    private function getPermutedUnits(array $parentPermutationUnits): array
    {
        $permutedUnits = $this->getNextCyclicPermutation($parentPermutationUnits);
        $fields = FieldIterator::mergeAll($permutedUnits);

        return $this->createUnitMatrices($fields->getValues());
    }

    private function getNextCyclicPermutation(array $permutationUnits): array
    {
        $headUnit = array_shift($permutationUnits);
        return array_merge($permutationUnits, [$headUnit]);
    }
}
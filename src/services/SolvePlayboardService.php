<?php
declare(strict_types=1);

namespace src\services;

use src\models\Playboard;

class SolvePlayboardService
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

    //TODO This method generalizes prefillByBlocksDiagonally(). Consider to keep only one method.
    public function solveByBlocksDiagonally(): void
    {
        $this->playboard->getFields()->setNonEmptyFieldsToFixed();

        $sortedBlockIndices = $this->getBlockIndicesSortedDiagonally();

        $counter = 0;
        while ($counter < $this->maxRounds && !($this->playboard->isValid() && $this->playboard->isComplete())) {

            $counter++;
            $this->playboard->getFields()->emptyNonFixedFields();
            shuffle($this->legalValues); //TODO Memorize shuffled results to avoid duplicate loops.

            foreach ($this->legalValues as $legalValue) {

                foreach ($sortedBlockIndices as $blockIndex) {

                    $block = $this->playboard->getBlocks()[$blockIndex["row"] . "-" . $blockIndex["col"]];

                    if (in_array($legalValue, $block->getFields()->getValues()->toArray())) {
                        continue;
                    }

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
}
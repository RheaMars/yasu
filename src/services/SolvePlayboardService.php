<?php
declare(strict_types=1);

namespace src\services;

use FilterIterator;
use src\exceptions\UnsolvableFieldException;
use src\iterators\FieldIterator;
use src\iterators\ValueIterator;
use src\models\Field;
use src\models\Playboard;
use src\models\ValueGroup;

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
                            $field->setToSolvedAmbiguously();
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

    public function solveByStrategies(): void
    {
        $deadend = false;

        while(!$deadend && !($this->playboard->isComplete() && $this->playboard->isValid())) {
            try {
                // Secure methods:
                $this->solveByUnambiguousStrategies();

                // Start guessing:
                $this->solveByAmbiguousMissingValuesInFields();
            }
            catch (UnsolvableFieldException $unsolvableFieldException)
            {
                $deadend = true;
            }
        }
    }

    /**
     * @throws UnsolvableFieldException
     */
    private function solveByUnambiguousStrategies(): void
    {
        $deadend = false;
        while (!$deadend) {
            $numberOfFieldsSolvedByMissingValuesInFields = $this->solveByUnambiguousMissingValuesInFields();
            $numberOfFieldsSolvedByMissingValuesInValueGroups = $this->solveByUnambiguousMissingValuesInValueGroups();

            if (0 === $numberOfFieldsSolvedByMissingValuesInFields + $numberOfFieldsSolvedByMissingValuesInValueGroups) {
                $deadend = true;
            }
                    }
                }

    /**
     * @throws UnsolvableFieldException
     */
    private function solveByUnambiguousMissingValuesInFields(): int
    {
        $numberOfFieldsSolved = 0;

        foreach ($this->playboard->getFields() as $field) {

            if (null !== $field->getValue()) {
                continue;
            }

            $possibleValues = $this->findPossibleValuesOfField($field)->toArray();

            if (0 === sizeof($possibleValues)) {
                throw new UnsolvableFieldException("Found no possible values for field!");
            }
            else if (1 === sizeof($possibleValues)) {
                $field->setValue($possibleValues[0]);
                $field->setToSolvedUnambiguously();

                $numberOfFieldsSolved++;
            }
        }

        return $numberOfFieldsSolved;
    }

    //TODO Refactor (foreach loop with break?)
    private function solveByAmbiguousMissingValuesInFields(): void
    {
        foreach ($this->playboard->getFields() as $field) {

            if (null !== $field->getValue()) {
                continue;
            }

            $possibleValues = $this->findPossibleValuesOfField($field)->toArray();

            if (2 <= sizeof($possibleValues)) {
                $field->setValue($possibleValues[0]); // TODO Hardcoded stuff
                $field->setToSolvedAmbiguously();
                break;
            }
        }
    }


    private function solveByUnambiguousMissingValuesInValueGroups(): int
    {
        $numberOfFieldsSolved = 0;

        // blocks
        foreach ($this->playboard->getBlocks() as $block) {

            $possibleFieldValues = [];
            foreach ($block->getFields() as $field) {
                if (null !== $field->getValue()) {
                    continue;
                }
                $possibleFieldValues[] = $this->findPossibleValuesOfField($field)->toArray();
            }

            $possibleFieldValuesMerged = array_merge(...$possibleFieldValues);
            $duplicateFieldValues = array_unique(array_values(array_diff_assoc($possibleFieldValuesMerged, array_unique($possibleFieldValuesMerged))));
            $uniqueFieldValues = array_diff(array_unique($possibleFieldValuesMerged), $duplicateFieldValues);

            foreach ($uniqueFieldValues as $uniqueFieldValue) {
                foreach ($block->getFields() as $field) {
                    if (null !== $field->getValue()) {
                        continue;
                    }
                    if (in_array($uniqueFieldValue, $this->findPossibleValuesOfField($field)->toArray())) {
                        $field->setValue($uniqueFieldValue);
                        $field->setToSolvedUnambiguously();
                        $numberOfFieldsSolved++;
                    }
                }
            }
        }

        // rows
        foreach ($this->playboard->getRows() as $row) {

            $possibleFieldValues = [];
            foreach ($row->getFields() as $field) {
                if (null !== $field->getValue()) {
                    continue;
                }
                $possibleFieldValues[] = $this->findPossibleValuesOfField($field)->toArray();
            }

            $possibleFieldValuesMerged = array_merge(...$possibleFieldValues);
            $duplicateFieldValues = array_unique(array_values(array_diff_assoc($possibleFieldValuesMerged, array_unique($possibleFieldValuesMerged))));
            $uniqueFieldValues = array_diff(array_unique($possibleFieldValuesMerged), $duplicateFieldValues);

            foreach ($uniqueFieldValues as $uniqueFieldValue) {
                foreach ($row->getFields() as $field) {
                    if (null !== $field->getValue()) {
                        continue;
                    }
                    if (in_array($uniqueFieldValue, $this->findPossibleValuesOfField($field)->toArray())) {
                        $field->setValue($uniqueFieldValue);
                        $field->setToSolvedUnambiguously();
                        $numberOfFieldsSolved++;
                    }
                }
            }
        }

        // columns
        foreach ($this->playboard->getColumns() as $column) {

            $possibleFieldValues = [];
            foreach ($column->getFields() as $field) {
                if (null !== $field->getValue()) {
                    continue;
                }
                $possibleFieldValues[] = $this->findPossibleValuesOfField($field)->toArray();
            }

            $possibleFieldValuesMerged = array_merge(...$possibleFieldValues);
            $duplicateFieldValues = array_unique(array_values(array_diff_assoc($possibleFieldValuesMerged, array_unique($possibleFieldValuesMerged))));
            $uniqueFieldValues = array_diff(array_unique($possibleFieldValuesMerged), $duplicateFieldValues);

            foreach ($uniqueFieldValues as $uniqueFieldValue) {
                foreach ($column->getFields() as $field) {
                    if (null !== $field->getValue()) {
                        continue;
                    }
                    if (in_array($uniqueFieldValue, $this->findPossibleValuesOfField($field)->toArray())) {
                        $field->setValue($uniqueFieldValue);
                        $field->setToSolvedUnambiguously();
                        $numberOfFieldsSolved++;
                    }
                }
            }
        }
        return $numberOfFieldsSolved;
    }

    private function getAmbiguousFields(FieldIterator $fields): FieldIterator
    {
        $ambiguousFields = new FieldIterator();
        foreach ($fields as $field) {
            if (null !== $field->getValue()) {
                continue;
            }
            if (sizeof($this->findPossibleValuesOfField($field)) > 1) {
                $ambiguousFields[] = $field;
            }
        }
        return $ambiguousFields;
    }


    private function findPossibleValuesOfField(Field $field): ValueIterator
    {
        $row = $this->playboard->getRows()[$field->getRowIndex()];
        $column = $this->playboard->getColumns()[$field->getColIndex()];
        $block = $this->playboard->getBlocks()[$field->getBlockIndex()];

        $valueGroups = [$row, $column, $block];

        $missingValues = [];

        foreach ($valueGroups as $valueGroup) {
            $missingValues[] = $this->findMissingValuesInValueGroup($valueGroup)->toArray();
        }

        return new ValueIterator(...array_intersect($this->legalValues, ...$missingValues));
    }

    private function findMissingValuesInValueGroup(ValueGroup $valueGroup): ValueIterator
    {
        $prefilledFields = $valueGroup->getFields()->getValues()->toArray();
        $missingFields = array_diff($this->legalValues, $prefilledFields);
        return new ValueIterator(...$missingFields);
    }
}
<?php
declare(strict_types=1);

namespace src\services;

use src\iterators\FieldIterator;
use src\models\Field;
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

    /**
     * Naive approach to solve a Sudoku by backtracking.
     */
    public function solveByBacktracking(FieldIterator $emptyFields): bool
    {
        foreach ($emptyFields as $emptyField) {
            $emptyField->setValidValues($this->playboard->getValidValuesForField($emptyField));
        }

        foreach ($emptyFields as $field) {

            foreach ($field->getValidValues() as $validValue) {

                $field->setValue($validValue);

                $sortedEmptyFieldsCopy = $emptyFields->getArrayCopy();
                array_shift($sortedEmptyFieldsCopy);
                $remainingEmptyFields = new FieldIterator(...$sortedEmptyFieldsCopy);

                if ($this->solveByBacktracking($remainingEmptyFields)) {
                    return true;
                }

                $field->setValue(null);
            }

            return false;
        }
        return true;
    }

    /**
     * Improved version of the naive approach to solve a Sudoku by backtracking.
     */
    public function solveByBacktrackingWithValenceSortedFields(FieldIterator $emptyFields): bool
    {
        foreach ($emptyFields as $emptyField) {
            $emptyField->setValidValues($this->playboard->getValidValuesForField($emptyField));
        }

        $sortedEmptyFields = $this->getFieldsSortedByNumberOfValidValuesAscending($emptyFields);

        foreach ($sortedEmptyFields as $field) {

            foreach ($field->getValidValues() as $validValue) {

                $field->setValue($validValue);

                $sortedEmptyFieldsCopy = $sortedEmptyFields->getArrayCopy();
                array_shift($sortedEmptyFieldsCopy);
                $remainingEmptyFields = new FieldIterator(...$sortedEmptyFieldsCopy);

                if ($this->solveByBacktrackingWithValenceSortedFields($remainingEmptyFields)) {
                    return true;
                }

                $field->setValue(null);
            }

            return false;
        }
        return true;
    }

    /**
     * Solve recursively by backtracking.
     *
     * Notions that are used:
     * - The "valence" of a field is the set of legal values that are valid for a given playboard configuration.
     * - A change in a field "affects" all fields in the same row, column, and block, that is, all of its neighbors; therefore, setting a value in a field
     *  directly reduces the possible values for all affected fields.
     *
     * In each recursive step:
     * - (re)calculate ("reset") the valences in the playboard lazily, that is, only for the empty fields that were affected
     *  by the last recursive step;
     * - sort the fields by their valence, from low to large;
     * - set the first possible value for the first field in the sorted list of fields;
     * - find the corresponding affected empty fields
     * - recurse on the tail of the sorted list of fields with the corresponding affected empty fields.
     */
    public function solveByBacktrackingWithValenceSortedFieldsAndLazyResetting(FieldIterator $emptyFields, FieldIterator $affectedFields): bool
    {
        foreach ($affectedFields as $affectedField) {
            $affectedField->setValidValues($this->playboard->getValidValuesForField($affectedField));
        }

        $sortedEmptyFields = $this->getFieldsSortedByNumberOfValidValuesAscending($emptyFields);

        foreach ($sortedEmptyFields as $field) {

            foreach ($field->getValidValues() as $validValue) {

                $field->setValue($validValue);

                // Recursive call with the tail of $sortedEmptyFields:
                $sortedEmptyFieldsCopy = $sortedEmptyFields->getArrayCopy();
                array_shift($sortedEmptyFieldsCopy);
                $remainingEmptyFields = new FieldIterator(...$sortedEmptyFieldsCopy);

                $currentAffectedFields = $this->getEmptyNeighboursOfField($field);

                if ($this->solveByBacktrackingWithValenceSortedFieldsAndLazyResetting($remainingEmptyFields, $currentAffectedFields)) {
                    return true;
                }

                $field->setValue(null);
            }

            foreach ($affectedFields as $affectedField) {
                $affectedField->setValidValues($this->playboard->getValidValuesForField($affectedField));
            }
            return false;
        }
        return true;
    }

    private function getFieldsSortedByNumberOfValidValuesAscending(FieldIterator $fields): FieldIterator
    {
        $fields = $fields->toArray();
        usort($fields, function (Field $a, Field $b) {
            $validValuesCountFieldA = $a->getValidValues()->count();
            $validValuesCountFieldB = $b->getValidValues()->count();
            if ($validValuesCountFieldA == $validValuesCountFieldB) {
                return 0;
            }
            return ($validValuesCountFieldA < $validValuesCountFieldB) ? -1 : 1;
        });
        return new FieldIterator(...$fields);
    }

    private function getEmptyNeighboursOfField(Field $field): FieldIterator
    {
        $affectedFields = array_merge(
            array_filter($this->playboard->getRowByIndex($field->getRowIndex())->getFields()->toArray(), function (Field $field) {
                return null === $field->getValue();
            }),
            array_filter($this->playboard->getColumnByIndex($field->getColIndex())->getFields()->toArray(), function (Field $field) {
                return null === $field->getValue();
            }),
            array_filter($this->playboard->getBlockByIndex($field->getBlockIndex())->getFields()->toArray(), function (Field $field) {
                return null === $field->getValue();
            }),
        );
        return new FieldIterator(...$affectedFields);
    }
}
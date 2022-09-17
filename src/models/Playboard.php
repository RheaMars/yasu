<?php

namespace src\models;

use Exception;
use src\collections\BlockCollection;
use src\collections\ColumnCollection;
use src\collections\FieldCollection;
use src\collections\IntegerCollection;
use src\collections\RowCollection;

class Playboard
{
    private int $baseSize;

    private FieldCollection $fields;

    private RowCollection $rows;

    private ColumnCollection $cols;

    private BlockCollection $blocks;

    public function __construct(int $baseSize)
    {
        if ($baseSize < 1) {
            throw new Exception("Base size must be at least 1.");
        }
        $this->baseSize = $baseSize;

        $this->createEmptyPlayboard();
    }

    public function setFieldsFromData(array $data): void
    {
        foreach ($data as $fieldData) {
            $field = $this->fields->getFieldByIndices($fieldData["row"], $fieldData["col"]);
            if ("" === $fieldData["val"]) {
                $field->setDigit(new Digit(null));
            } else {
                $field->setDigit(new Digit((int)$fieldData["val"]));
            }

            if ("true" === $fieldData["isFixed"]) {
                $field->setToFixed();
            }
        }
    }

    public function generatePlayboardHtml(): string
    {
        $baseSize = $this->baseSize;

        $html = "<table class='playboard'><tbody>";

        for ($playboardRow = 1; $playboardRow <= $baseSize; $playboardRow++) {
            $html .= "<tr>";
            for ($playboardCol = 1; $playboardCol <= $baseSize; $playboardCol++) {
                $html .= "<td>";
                $html .= "<table class='block'>";
                $html .= "<tbody>";
                for ($blockRow = 1; $blockRow <= $baseSize; $blockRow++) {
                    $html .= "<tr>";
                    for ($blockCol = 1; $blockCol <= $baseSize; $blockCol++) {
                        $row = ($playboardRow - 1) * $baseSize + $blockRow;
                        $col = ($playboardCol - 1) * $baseSize + $blockCol;
                        $field = $this->fields[$row . "-" . $col];
                        $value = $field->getDigit()->getValue();

                        $disabledProperty = "";
                        $fixedClass = "";

                        if ($field->isValueFixed()) {
                            $disabledProperty = "disabled";
                            $fixedClass = "isFixed";
                        }

                        $html .= "<td class='" . $fixedClass . "'>";
                        $html .= "<input " . $disabledProperty . " 
                            class='field " . $fixedClass . "'
                            data-row='" . $row . "'
                            data-col='" . $col . "'
                            data-block-row='" . $blockRow . "'
                            data-block-col='" . $blockCol . "'
                            data-playboard-row='" . $playboardRow . "'
                            data-playboard-col='" . $playboardCol . "'
                            value='" . $value . "'/>";
                        $html .= "</td>";
                    }
                    $html .= "</tr>";
                }
                $html .= "</tbody>";
                $html .= "</table>";
                $html .= "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody>";
        $html .= "</table>";
        return $html;
    }

    public function isValid(): bool
    {
        $digitGroups = array_merge($this->blocks->toArray(), $this->rows->toArray(), $this->cols->toArray());

        foreach ($digitGroups as $group) {
            /** @var $group DigitGroup */
            if (!$group->isValid()) {
                return false;
            }
        }

        return true;
    }

    // TODO Consider to refactor this method
    public function getInvalidFields(): FieldCollection
    {
        $invalidFields = new FieldCollection();
        $invalidFieldsIndices = [];

        foreach ($this->fields as $field) {
            if ($field->isValueFixed() || null === $field->getDigit()->getValue()) {
                continue;
            }

            $row = $this->rows[$field->getRowIndex()];
            $col = $this->cols[$field->getColIndex()];
            $block = $this->blocks[$field->getBlockIndex()];

            $digitGroups = [$row, $col, $block];

            foreach ($digitGroups as $group) {

                foreach ($group->getFields() as $fieldOfDigitGroup) {
                    // skip the field that is currently checked
                    if ($fieldOfDigitGroup->getRowIndex() === $field->getRowIndex()
                        && $fieldOfDigitGroup->getColIndex() === $field->getColIndex()) {
                        continue;
                    }
                    if ($field->getDigit()->getValue() === $fieldOfDigitGroup->getDigit()->getValue()) {
                        if (!in_array($field->getRowIndex() . "-" . $field->getColIndex(), $invalidFieldsIndices)) {
                            $invalidFields[] = $field;
                            $invalidFieldsIndices[] = $field->getRowIndex() . "-" . $field->getColIndex();
                        }
                    }
                }
            }
        }

        return $invalidFields;
    }

    public function isComplete(): bool
    {
        foreach ($this->fields as $field) {
            if (null === $field->getDigit()->getValue()) {
                return false;
            }
        }
        return true;
    }

    public function emptyFieldsByPercentage(float $percentage): void
    {
        if ($percentage < 0.0 || $percentage > 1.0) {
            throw new Exception("Percentage must be between 0.0 and 1.0");
        }

        if ($this->baseSize <= 1) {
            return;
        }

        switch ($percentage) {
            case 0.0:
                return;
            case 1.0:
                $this->fields->emptyDigitValues();
                break;
            default:
                $numberOfFieldsToEmpty = (int)(round($percentage * pow($this->baseSize, 4)));
                $randomFieldKeys = array_rand($this->fields->toArray(), $numberOfFieldsToEmpty);
                foreach ($randomFieldKeys as $key) {
                    $field = $this->fields[$key];
                    $field->setDigit(new Digit(null));
                }
        }
    }

    public function setPrefilledFieldsToFixed(): void
    {
        $this->fields->setNonEmptyFieldsToFixed();
    }

    public function prefillFields(): void
    {
        $maxRounds = 100 * pow($this->baseSize, 2);
        //$this->fields->prefillRandomly($this->baseSize);
        //$this->prefillFieldsByBlocksDiagonally($maxRounds);
        //$this->prefillFieldsByRows($maxRounds);
        $this->prefillFieldsByPlayboardRows($maxRounds);
        //$this->prefillFieldsByPermutations();
    }

    private function createEmptyPlayboard()
    {
        $fields = $this->createEmptyFields($this->baseSize);

        $this->fields = $fields;
        $this->rows = $this->createEmptyRows($fields);
        $this->cols = $this->createEmptyColumns($fields);
        $this->blocks = $this->createEmptyBlocks($fields);
    }

    private function createEmptyFields($baseSize): FieldCollection
    {
        $fields = new FieldCollection();
        for ($row = 1; $row <= pow($baseSize, 2); $row++) {
            for ($col = 1; $col <= pow($baseSize, 2); $col++) {
                $fields[$row . "-" . $col] = new Field($baseSize, $row, $col, new Digit(null));
            }
        }
        return $fields;
    }

    private function createEmptyRows(FieldCollection $fields): RowCollection
    {
        $rows = new RowCollection();
        foreach ($fields as $field) {
            $rowIndex = $field->getRowIndex();

            if (!isset($rows[$rowIndex])) {
                $row = new Row($rowIndex, $this->baseSize);
                $rows[$rowIndex] = $row;
            }
            $row = $rows[$rowIndex];
            $row->addField($field);
        }
        return $rows;
    }

    private function createEmptyColumns(FieldCollection $fields): ColumnCollection
    {
        $cols = new ColumnCollection();
        foreach ($fields as $field) {
            $colIndex = $field->getColIndex();

            if (!isset($cols[$colIndex])) {
                $col = new Column($colIndex, $this->baseSize);
                $cols[$colIndex] = $col;
            }
            $col = $cols[$colIndex];
            $col->addField($field);
        }
        return $cols;
    }

    private function createEmptyBlocks(FieldCollection $fields): BlockCollection
    {
        $blocks = new BlockCollection();
        foreach ($fields as $field) {
            $playboardRowIndex = $field->getPlayboardRowIndex();
            $playboardColIndex = $field->getPlayboardColIndex();
            $blockIndex = $playboardRowIndex . "-" . $playboardColIndex;

            if (!isset($blocks[$blockIndex])) {
                $block = new Block($blockIndex, $playboardRowIndex, $playboardColIndex, $this->baseSize);
                $blocks[$blockIndex] = $block;
            }
            $block = $blocks[$blockIndex];
            $block->addField($field);
        }
        return $blocks;
    }

    /**
     * This prefills the fields of the first block in a shuffled manner
     * and then fills the next blocks based on a "parent block" (left or upper),
     * by permuting the block rows or columns of the latter.
     * It is a non-brute-force method to prefill fields.
     */
    private function prefillFieldsByPermutations(): void
    {
        foreach ($this->blocks as $block) {

            /** @var $block Block */

            $parentBlock = $this->getParentBlock($block);

            // prefill fields of first block with randomly shuffled values
            if (null === $parentBlock) {
                $digits = range(1, pow($this->baseSize, 2));
                shuffle($digits);
                $digitUnits = $this->createUnitMatrices(new IntegerCollection($digits))["rowUnits"];
                $block->prefillFromMatrix($digitUnits);
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

    private function getPermutedUnits(array $parentPermutationUnits): array
    {
        $permutedUnits = $this->getNextCyclicPermutation($parentPermutationUnits);
        $fields = FieldCollection::merge($permutedUnits);

        return $this->createUnitMatrices($fields->getDigitValues());
    }

    /**
     * This returns an associative array of length 2,
     * where the first component "rowUnits" has an array of "row units" as a value,
     * and the second component "colUnits" has an array of "column units" as a value.
     * In other words, this arranges the given digit values as matrix rows (first output)
     * as well as matrix columns (second output).
     *
     * Example:
     * Input: [1, 2, 3, 4, 5, 6, 7, 8, 9]
     * Output: ["rowUnits" => [[1, 2, 3], [4, 5, 6], [7, 8, 9]], "colUnits" => [[1, 4, 7], [2, 5, 8], [3, 6, 9]]]
     *
     */
    private function createUnitMatrices(IntegerCollection $digitValues): array
    {
        $rowUnits = array_chunk($digitValues->toArray(), $this->baseSize);
        $colUnits = [];
        for ($i = 0; $i < $this->baseSize; $i++) {
            $colUnits[$i] = [];
            foreach ($rowUnits as $row) {
                $colUnits[$i] = array_merge($colUnits[$i], [$row[$i]]);
            }
        }

        return ["rowUnits" => $rowUnits, "colUnits" => $colUnits];
    }

    private function getNextCyclicPermutation(array $permutationUnits): array
    {
        $headUnit = array_shift($permutationUnits);
        return array_merge($permutationUnits, [$headUnit]);
    }

    private function getParentBlock(Block $block): ?Block
    {
        if (1 === $block->getPlayboardRowIndex() && 1 === $block->getPlayboardColIndex()) {
            return null;
        }
        if (1 < $block->getPlayboardColIndex()) {
            return $this->blocks->getBlockByPlayboardIndices($block->getPlayboardRowIndex(), $block->getPlayboardColIndex() - 1);
        }
        if (1 < $block->getPlayboardRowIndex()) {
            return $this->blocks->getBlockByPlayboardIndices($block->getPlayboardRowIndex() - 1, $block->getPlayboardColIndex());
        }
        return null;
    }

    private function prefillFieldsByBlocksDiagonally(int $maxRounds): void
    {
        $digits = range(1, pow($this->baseSize, 2));

        $sortedBlockIndices = $this->getBlockIndicesSortedDiagonally();

        $counter = 0;
        while ($counter < $maxRounds && !($this->isValid() && $this->isComplete())) {
            $counter++;
            $this->emptyFieldsByPercentage(1.0);
            shuffle($digits);

            foreach ($digits as $digit) {
                foreach ($sortedBlockIndices as $blockIndex) {

                    $block = $this->blocks[$blockIndex["row"] . "-" . $blockIndex["col"]];

                    $blockFields = $block->getFields()->toArray();
                    shuffle($blockFields);

                    foreach ($blockFields as $field) {

                        if (null !== $field->getDigit()->getValue()) {
                            continue;
                        }

                        $field->setDigit(new Digit($digit));

                        /** @var $row Row */
                        $row = $this->rows[$field->getRowIndex()];
                        /** @var $col Column */
                        $col = $this->cols[$field->getColIndex()];
                        /** @var $block Block */
                        $block = $this->blocks[$field->getBlockIndex()];

                        if ($row->isValid() && $col->isValid() && $block->isValid()) {
                            break;
                        }
                        $field->setDigit(new Digit(null));
                    }

                    // if field could not be filled, the playboard is invalid - try again
                    if (null === $field->getDigit()->getValue()) {
                        break 2;
                    }
                }
            }
        }
    }

    private function getBlockIndicesSortedDiagonally(): array
    {
        $indices = [];
        foreach ($this->blocks as $block) {
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

    private function prefillFieldsByPlayboardRows(int $maxRounds): void
    {
        $digits = range(1, pow($this->baseSize, 2));

        $counter = 0;
        while ($counter < $maxRounds && !($this->isValid() && $this->isComplete())) {
            $counter++;
            $this->emptyFieldsByPercentage(1.0);
            shuffle($digits);

            foreach ($digits as $digit) {
                foreach ($this->blocks as $block) {

                    $blockFields = $block->getFields()->toArray();
                    shuffle($blockFields);

                    foreach ($blockFields as $field) {

                        if (null !== $field->getDigit()->getValue()) {
                            continue;
                        }

                        $field->setDigit(new Digit($digit));

                        /** @var $row Row */
                        $row = $this->rows[$field->getRowIndex()];
                        /** @var $col Column */
                        $col = $this->cols[$field->getColIndex()];
                        /** @var $block Block */
                        $block = $this->blocks[$field->getBlockIndex()];

                        if ($row->isValid() && $col->isValid() && $block->isValid()) {
                            break;
                        }

                        $field->setDigit(new Digit(null));
                    }

                    // if field could not be filled, the playboard is invalid - try again
                    if (null === $field->getDigit()->getValue()) {
                        break 2;
                    }
                }
            }
        }
    }

    /**
     * Note: This approach doesn't give good results! We leave it here for test purposes.
     */
    private function prefillFieldsByRows(int $maxRounds): void
    {

        $digits = range(1, pow($this->baseSize, 2));

        $counter = 0;
        while ($counter < $maxRounds && !($this->isValid() && $this->isComplete())) {
            $counter++;
            $this->emptyFieldsByPercentage(1.0);
            shuffle($digits);

            foreach ($this->fields as $field) {
                $rowIndex = $field->getRowIndex();
                $colIndex = $field->getColIndex();
                $blockIndex = $field->getBlockIndex();
                foreach ($digits as $digit) {
                    $field->setDigit(new Digit($digit));

                    /** @var $row Row */
                    $row = $this->rows[$rowIndex];
                    /** @var $col Column */
                    $col = $this->cols[$colIndex];
                    /** @var $block Block */
                    $block = $this->blocks[$blockIndex];

                    if ($row->isValid() && $col->isValid() && $block->isValid()) {
                        break;
                    }
                    $field->setDigit(new Digit(null));
                }

                // if field could not be filled, the playboard is invalid - try again
                if (null === $field->getDigit()->getValue()) {
                    break;
                }
            }
        }
    }
}
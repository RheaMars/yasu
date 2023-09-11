<?php
declare(strict_types=1);

namespace src\models;

use Exception;
use src\iterators\BlockIterator;
use src\iterators\ColumnIterator;
use src\iterators\FieldIterator;
use src\iterators\RowIterator;
use src\iterators\PlayboardRowIterator;
use src\iterators\PlayboardColumnIterator;
use src\iterators\ValueIterator;
use src\services\PrefillPlayboardService;
use src\services\RandomizePlayboardService;
use src\services\SolvePlayboardService;

class Playboard
{
    private int $baseSize;

    private FieldIterator $fields;

    private RowIterator $rows;

    private ColumnIterator $columns;

    private BlockIterator $blocks;

    private PlayboardRowIterator $playboardRows;

    private PlayboardColumnIterator $playboardColumns;

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
            $field = $this->fields[$fieldData["row"]."-".$fieldData["col"]];
            if ("" === $fieldData["val"]) {
                $field->setValue(null);
            } else {
                $field->setValue((int)$fieldData["val"]);
            }

            if (true === $fieldData["isFixed"]) {
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
                        $value = $field->getValue();

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

    public function getFields(): FieldIterator
    {
        return $this->fields;
    }

    public function getEmptyFields(): FieldIterator
    {
        $emptyFields = array_filter($this->fields->toArray(), function (Field $field) {
            return null === $field->getValue();
        });

        return new FieldIterator(...$emptyFields);
    }

    public function getNonEmptyUnfixedFields(): FieldIterator
    {
        $nonEmptyUnfixedFields = array_filter($this->fields->toArray(), function (Field $field) {
            return false === $field->isValueFixed() && null !== $field->getValue();
        });

        return new FieldIterator(...$nonEmptyUnfixedFields);
    }

    public function getBaseSize(): int
    {
        return $this->baseSize;
    }

    public function getRows(): RowIterator
    {
        return $this->rows;
    }

    public function getRowsByPlayboardRowIndex(int $playboardRowIndex): RowIterator
    {
        $rows = new RowIterator();
        foreach ($this->fields as $field){
            $fieldRowIndex = $field->getRowIndex();
            if ($field->getPlayboardRowIndex() === $playboardRowIndex
                && !isset($rows[$fieldRowIndex])){
                $rows[$fieldRowIndex] = $this->rows[$fieldRowIndex];
            }
        }
        return $rows;
    }

    public function getColumnsByPlayboardColumnIndex(int $playboardColumnIndex): ColumnIterator
    {
        $columns = new ColumnIterator();
        foreach ($this->fields as $field){
            $fieldColumnIndex = $field->getColIndex();
            if ($field->getPlayboardColumnIndex() === $playboardColumnIndex
                && !isset($columns[$fieldColumnIndex])){
                $columns[$fieldColumnIndex] = $this->columns[$fieldColumnIndex];
            }
        }
        return $columns;
    }
    public function getRowByIndex(int $index): Row
    {
        $rows = new RowIterator(...$this->rows);
        foreach ($rows as $row){
            if ($index === $row->getIndex()){
                return $row;
            }
        }
        throw new Exception("No row with index " . $index . " in playboard");
    }

    public function getColumnByIndex(int $index): Column
    {
        $columns = new ColumnIterator(...$this->columns);
        foreach ($columns as $column){
            if ($index === $column->getIndex()){
                return $column;
            }
        }
        throw new Exception("No column with index " . $index . " in playboard");
    }

    public function getBlockByIndex(string $index): Block
    {
        $blocks = new BlockIterator(...$this->blocks);
        foreach ($blocks as $block){
            if ($index === $block->getPlayboardRowIndex()."-".$block->getPlayboardColumnIndex()){
                return $block;
            }
        }
        throw new Exception("No block with index " . $index . " in playboard");

    }

    public function getColumns(): ColumnIterator
    {
        return $this->columns;
    }

    public function getBlocks(): BlockIterator
    {
        return $this->blocks;
    }

    public function getPlayboardRows(): PlayboardRowIterator
    {
        return $this->playboardRows;
    }

    public function getPlayboardColumns(): PlayboardColumnIterator
    {
        return $this->playboardColumns;
    }

    public function isValid(): bool
    {
        if (0 === sizeof($this->getInvalidFields()->toArray())) {
            return true;
        }
        return false;
    }

    public function getInvalidFields(): FieldIterator
    {
        $invalidFields = [];
        $valueGroupIterators = [$this->rows, $this->columns, $this->blocks];
        foreach ($valueGroupIterators as $groupIterator) {
            foreach ($groupIterator as $group) {
                $invalidFields[] = $group->getInvalidFields()->toArray();
            }
        }
        $invalidFields = array_unique(array_merge(...$invalidFields), SORT_REGULAR);

        return new FieldIterator(...$invalidFields);
    }

    public function getValidValuesForField(Field $field): ValueIterator
    {
        $block = $this->getBlocks()[$field->getBlockIndex()];
        $row = $this->getRows()[$field->getRowIndex()];
        $column = $this->getColumns()[$field->getColIndex()];

        $remainingValues = [];
        $remainingValues[] = $block->getRemainingValues()->toArray();
        $remainingValues[] = $row->getRemainingValues()->toArray();
        $remainingValues[] = $column->getRemainingValues()->toArray();
        $validValues = array_intersect(...$remainingValues);

        return new ValueIterator(...$validValues);
    }

    public function getFieldsPreparedForHtml(FieldIterator $fields): array
    {
        $fieldsPreparedForHtml = [];
        foreach ($fields as $field) {
            $fieldsPreparedForHtml[] = [
                "row" => $field->getRowIndex(),
                "col" => $field->getColIndex(),
                "value" => $field->getValue()
            ];
        }
        return $fieldsPreparedForHtml;
    }

    public function isComplete(): bool
    {
        return $this->fields->allValuesSet();
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
                $this->fields->emptyValues();
                break;
            default:
                $numberOfFieldsToEmpty = (int)(round($percentage * pow($this->baseSize, 4)));
                $randomFieldKeys = array_rand($this->fields->toArray(), $numberOfFieldsToEmpty);
                foreach ($randomFieldKeys as $key) {
                    $field = $this->fields[$key];
                    $field->setValue(null);
                }
        }
    }

    public function setPrefilledFieldsToFixed(): void
    {
        $this->fields->setNonEmptyFieldsToFixed();
    }

    public function prefillFields(): void
    {
        $service = new PrefillPlayboardService($this);
        //$service->prefillRandomly();
        //$service->prefillByBlocksDiagonally();
        //$service->prefillByRows();
        //$service->prefillByPlayboardRows();
        $service->prefillByPermutations();
    }

    public function solve(): bool
    {
        $service = new SolvePlayboardService($this, 1000);

        $emptyFields = $this->getEmptyFields();

        //$service->solveByBacktracking($emptyFields);
        //$service->solveByBacktrackingWithValenceSortedFields($emptyFields);
        $service->solveByBacktrackingWithValenceSortedFieldsAndLazyResetting($emptyFields, $emptyFields);

        if ($this->isValid() && $this->isComplete()) {
            return true;
        }

        return false;
    }

    public function randomize(): void
    {
        $service = new RandomizePlayboardService($this);
        $service->permuteRowsWithinPlayboardRows();
        $service->permuteColumnsWithinPlayboardColumns();
    }

    private function createEmptyPlayboard()
    {
        $fields = $this->createEmptyFields($this->baseSize);

        $this->fields = $fields;
        $this->rows = $this->createEmptyRows($fields);
        $this->columns = $this->createEmptyColumns($fields);
        $this->blocks = $this->createEmptyBlocks($fields);
        $this->playboardRows = $this->createEmptyPlayboardRows($fields);
        $this->playboardColumns = $this->createEmptyPlayboardColumns($fields);
    }

    private function createEmptyFields($baseSize): FieldIterator
    {
        $fields = new FieldIterator();
        for ($row = 1; $row <= pow($baseSize, 2); $row++) {
            for ($col = 1; $col <= pow($baseSize, 2); $col++) {
                $fields[$row . "-" . $col] = new Field($baseSize, $row, $col, null);
            }
        }
        return $fields;
    }

    private function createEmptyRows(FieldIterator $fields): RowIterator
    {
        $rows = new RowIterator();
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

    private function createEmptyColumns(FieldIterator $fields): ColumnIterator
    {
        $cols = new ColumnIterator();
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

    private function createEmptyBlocks(FieldIterator $fields): BlockIterator
    {
        $blocks = new BlockIterator();
        foreach ($fields as $field) {
            $playboardRowIndex = $field->getPlayboardRowIndex();
            $playboardColIndex = $field->getPlayboardColumnIndex();
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

    private function createEmptyPlayboardRows(FieldIterator $fields): PlayboardRowIterator
    {
        $playboardRows = new PlayboardRowIterator();
        foreach ($fields as $field) {
            $playboardRowIndex = $field->getPlayboardRowIndex();

            if (!isset($playboardRows[$playboardRowIndex])) {
                $playboardRow = new PlayboardRow($playboardRowIndex);
                $playboardRows[$playboardRowIndex] = $playboardRow;
            }
            $playboardRow = $playboardRows[$playboardRowIndex];
            $playboardRow->addField($field);
        }
        return $playboardRows;
    }

    private function createEmptyPlayboardColumns(FieldIterator $fields): PlayboardColumnIterator
    {
        $playboardColumns = new PlayboardColumnIterator();
        foreach ($fields as $field) {
            $playboardColumnIndex = $field->getPlayboardColumnIndex();

            if (!isset($playboardColumns[$playboardColumnIndex])) {
                $playboardColumn = new PlayboardColumn($playboardColumnIndex);
                $playboardColumns[$playboardColumnIndex] = $playboardColumn;
            }
            $playboardColumn = $playboardColumns[$playboardColumnIndex];
            $playboardColumn->addField($field);
        }
        return $playboardColumns;
    }
}
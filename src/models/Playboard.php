<?php
declare(strict_types=1);

namespace src\models;

use Exception;
use src\collections\BlockCollection;
use src\collections\ColumnCollection;
use src\collections\FieldCollection;
use src\collections\RowCollection;
use src\services\PrefillPlayboardService;

class Playboard
{
    private int $baseSize;

    private FieldCollection $fields;

    private RowCollection $rows;

    private ColumnCollection $columns;

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
            $field = $this->fields->getFieldByIndices((int)$fieldData["row"], (int)$fieldData["col"]);
            if ("" === $fieldData["val"]) {
                $field->setValue(null);
            } else {
                $field->setValue((int)$fieldData["val"]);
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

    public function getFields(): FieldCollection
    {
        return $this->fields;
    }

    public function getBaseSize(): int
    {
        return $this->baseSize;
    }

    public function getRows(): RowCollection
    {
        return $this->rows;
    }

    public function getColumns(): ColumnCollection
    {
        return $this->columns;
    }

    public function getBlocks(): BlockCollection
    {
        return $this->blocks;
    }

    public function isValid(): bool
    {
        if (0 === sizeof($this->getInvalidFields()->toArray())) {
            return true;
        }
        return false;
    }

    public function getInvalidFields(): FieldCollection
    {
        $invalidFields = [];
        $valueGroupCollections = [$this->rows, $this->columns, $this->blocks];
        foreach ($valueGroupCollections as $groupCollection) {
            foreach ($groupCollection as $group) {
                $invalidFields[] = $group->getInvalidFields()->toArray();
            }
        }
        $invalidFields = array_unique(array_merge(...$invalidFields), SORT_REGULAR);

        return new FieldCollection($invalidFields);
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
        $maxRounds = 100 * pow($this->baseSize, 2);
        $service = new PrefillPlayboardService();
        //$service->prefillRandomly($this);
        //$service->prefillByBlocksDiagonally($this, $maxRounds);
        //$service->prefillByRows($this, $maxRounds);
        //$service->prefillByPlayboardRows($this, $maxRounds);
        $service->prefillByPermutations($this);
    }

    private function createEmptyPlayboard()
    {
        $fields = $this->createEmptyFields($this->baseSize);

        $this->fields = $fields;
        $this->rows = $this->createEmptyRows($fields);
        $this->columns = $this->createEmptyColumns($fields);
        $this->blocks = $this->createEmptyBlocks($fields);
    }

    private function createEmptyFields($baseSize): FieldCollection
    {
        $fields = new FieldCollection();
        for ($row = 1; $row <= pow($baseSize, 2); $row++) {
            for ($col = 1; $col <= pow($baseSize, 2); $col++) {
                $fields[$row . "-" . $col] = new Field($baseSize, $row, $col, null);
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
}
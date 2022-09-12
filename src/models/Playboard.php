<?php
namespace src\models;

use Exception;

class Playboard
{
    private int $baseSize;

    /** @var Field[] */
    private array $fields = [];

    /** @var Row[] */
    private array $rows = [];

    /** @var Column[] */
    private array $cols = [];

    /** @var Block[] */
    private array $blocks = [];

    public function __construct(int $baseSize)
    {
        if ($baseSize < 1) {
            throw new Exception("Base size must be at least 1.");
        }
        $this->baseSize = $baseSize;

        // create fields, rows, columns, and blocks
        $fields = $this->createFields($baseSize);
        $this->fields = $fields;
        $this->rows = $this->createRows($fields);
        $this->cols = $this->createColumns($fields);
        $this->blocks = $this->createBlocks($fields);

//        echo "<pre>";
//        var_dump($this->cols);

//        echo "<pre>";
//        var_dump($this->blocks);

        $this->prefillFields();
//        $this->prefillFieldsRandomly();

        $this->isValid();
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getBaseSize(): int
    {
        return $this->baseSize;
    }

    private function createFields($baseSize): array
    {
        $fields = [];
        for($row = 1; $row <= pow($baseSize, 2); $row++) {
            for($col = 1; $col <= pow($baseSize, 2); $col++) {
                $fields[$row."-".$col] = new Field($baseSize, $row, $col, new Digit(null, $this->baseSize));
            }
        }
        return $fields;
    }

    private function createRows(array $fields): array
    {
        $rows = [];
        foreach ($fields as $field) {
            $rowIndex = $field->getRowIndex();

            if (!isset($rows[$rowIndex])) {
                $row = new Row($rowIndex);
                $rows[$rowIndex] = $row;
            }
            $row = $rows[$rowIndex];
            $row->addField($field);
        }
        return $rows;
    }

    private function createColumns(array $fields): array
    {
        $cols = [];
        foreach ($fields as $field) {
            $colIndex = $field->getColIndex();

            if (!isset($cols[$colIndex])) {
                $col = new Column($colIndex);
                $cols[$colIndex] = $col;
            }
            $col = $cols[$colIndex];
            $col->addField($field);
        }
        return $cols;
    }

    private function createBlocks(array $fields): array
    {
        $blocks = [];
        foreach ($fields as $field) {
            $playboardRowIndex = $field->getPlayboardRowIndex();
            $playboardColIndex = $field->getPlayboardColIndex();
            $blockIndex = $playboardRowIndex."-".$playboardColIndex;

            if (!isset($blocks[$blockIndex])) {
                $block = new Block($blockIndex);
                $blocks[$blockIndex] = $block;
            }
            $block = $blocks[$blockIndex];
            $block->addField($field);
        }
        return $blocks;
    }

    private function prefillFieldsRandomly(): void
    {
        foreach($this->fields as $field) {
            $field->setDigit(Digit::getRandomDigit($this->baseSize));
        }
    }

    private function prefillFields(): void
    {
        // TODO

        foreach($this->fields as $field) {
            $digits = range(1, pow($this->baseSize, 2));
//            shuffle($digits);
            $rowIndex = $field->getRowIndex();
            $colIndex = $field->getColIndex();
            $blockIndex = $field->getBlockIndex();
            foreach ($digits as $digit){
                $field->setDigit(new Digit($digit, $this->baseSize));
                $row = $this->rows[$rowIndex];
                $col = $this->cols[$colIndex];
                $block = $this->blocks[$blockIndex];
                if ($this->isValidDigitGroup($row) && $this->isValidDigitGroup($col) && $this->isValidDigitGroup($block)){
                    //echo $this->generatePlayboardHtml();
                    //echo "<br/><br/>";
                    break;
                }
                $field->setDigit(new Digit(null, $this->baseSize));
            }
//            echo "<pre>";
//            var_dump($digits);
//            exit;
//            while(!$this->isValidDigitGroup($row) || !$this->isValidDigitGroup($col) || !$this->isValidDigitGroup($block)){
//                ...;
//            }
        }
    }

    private function generatePlayboardHtml(): string
    {
        $fields = $this->getFields();
        $baseSize = $this->getBaseSize();

        $html = '<table class="playboard"><tbody>';

        for($playboardRow = 1; $playboardRow <= $baseSize; $playboardRow++) {
            $html .= "<tr>";
            for($playboardCol = 1; $playboardCol <= $baseSize; $playboardCol++) {
                $html .= "<td>";
                $html .= "<table class='block'>";
                $html .= "<tbody>";
                for($blockRow = 1; $blockRow <= $baseSize; $blockRow++) {
                    $html .= "<tr>";
                    for($blockCol = 1; $blockCol <= $baseSize; $blockCol++) {
                        $row = ($playboardRow - 1) * $baseSize + $blockRow;
                        $col = ($playboardCol - 1) * $baseSize + $blockCol;
                        $field = $fields[$row."-".$col];
                        $value = $field->getDigit()->getValue();

                        $html .= "<td>";
                        $html .= "<input class='field row-".$row." col-".$col." block-row-".$blockRow." block-col-".$blockCol." playboard-row-".$playboardRow." playboard-col-".$playboardCol."' value='".$value."'/>";
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

    private function isValid(): bool
    {
        $digitGroups = array_merge($this->blocks, $this->rows, $this->cols);

        foreach($digitGroups as $group) {
            if (!$this->isValidDigitGroup($group)){
                return false;
            }
        }

        return true;
    }

    private function isValidDigitGroup(DigitGroup $group): bool
    {
        $fieldValues = [];

        foreach($group->getFields() as $field) {
            $value = $field->getDigit()->getValue();
            if ($value !== null){
                $fieldValues[] = $value;
            }
        }

        if (sizeof($fieldValues) != sizeof(array_unique($fieldValues))) {
//            echo "Invalid digit group: " . $group->getType() . $group->getIndex();
            return false;
        }
        return true;
    }
}
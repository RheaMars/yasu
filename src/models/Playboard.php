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

    private bool $isCorrectlyInitialized = false;

    public function initialize(int $baseSize, float $level)
    {
        if ($baseSize < 1) {
            throw new Exception("Base size must be at least 1.");
        }
        $this->baseSize = $baseSize;

        $this->createFrame();

        $this->prefillFields();
        if ($this->isValid() && $this->isComplete()) {
            $this->isCorrectlyInitialized = true;
            $this->prepareForGame($level);
        }
    }

    public function initializeFromData(array $data)
    {
        $this->baseSize = pow(sizeof($data), 1/4);

        $this->createFrame();

        foreach ($data as $fieldData) {
            $field = $this->getFieldByIndices($fieldData["row"], $fieldData["col"]);
            if ("" === $fieldData["val"]){
                $field->setDigit(new Digit(null, $this->baseSize));
            }
            else {
                $field->setDigit(new Digit((int)$fieldData["val"], $this->baseSize));
            }

            if ("true" === $fieldData["isFixed"]){
                $field->setToFixed();
            }
        }
    }

    private function getFieldByIndices(int $rowIndex, int $colIndex): Field
    {
        foreach ($this->fields as $field){
            if ($rowIndex === $field->getRowIndex() && $colIndex === $field->getColIndex()) {
                return $field;
            }
        }

        throw new Exception("No field in given playboard has indices ".$rowIndex."-".$colIndex);
    }

    private function createFrame()
    {
        $fields = $this->createFields($this->baseSize);
        $this->fields = $fields;
        $this->rows = $this->createRows($fields);
        $this->cols = $this->createColumns($fields);
        $this->blocks = $this->createBlocks($fields);        
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
        for ($row = 1; $row <= pow($baseSize, 2); $row++) {
            for ($col = 1; $col <= pow($baseSize, 2); $col++) {
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
                $block = new Block($blockIndex, $playboardRowIndex, $playboardColIndex);
                $blocks[$blockIndex] = $block;
            }
            $block = $blocks[$blockIndex];
            $block->addField($field);
        }
        return $blocks;
    }

    public function isCorrectlyInitialized(): bool
    {
        return $this->isCorrectlyInitialized;
    }

    public function generatePlayboardHtml(): string
    {
        $fields = $this->getFields();
        $baseSize = $this->getBaseSize();

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
                        $field = $fields[$row."-".$col];
                        $value = $field->getDigit()->getValue();

                        $disabledProperty = "";
                        $fixedClass = "";

                        if ($field->isValueFixed()){
                            $disabledProperty = "disabled";
                            $fixedClass = "isFixed";
                        }

                        $html .= "<td class='" . $fixedClass . "'>";
                        $html .= "<input " . $disabledProperty . " 
                            class='field " . $fixedClass ."'
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
        $digitGroups = array_merge($this->blocks, $this->rows, $this->cols);

        foreach ($digitGroups as $group) {
            if (!$this->isValidDigitGroup($group)){
                return false;
            }
        }

        return true;
    }

    /**
     * @return array Field[]
     */
    public function getInvalidFields(): array
    {
        $invalidFields = [];
        $invalidFieldsIndices = [];

        foreach($this->fields as $field) {
            if ($field->isValueFixed() || null === $field->getDigit()->getValue()) {
                continue;
            }

            $row = $this->rows[$field->getRowIndex()];
            $col = $this->cols[$field->getColIndex()];
            $block = $this->blocks[$field->getBlockIndex()];

            $digitGroups = [$row, $col, $block];

            foreach ($digitGroups as $group) {
                foreach($group->getFields() as $fieldOfDigitGroup) {
                    // skip the field which is currently checked
                    if ($fieldOfDigitGroup->getRowIndex() === $field->getRowIndex()
                        && $fieldOfDigitGroup->getColIndex() === $field->getColIndex()) {
                        continue;
                    }
                    if ($field->getDigit()->getValue() === $fieldOfDigitGroup->getDigit()->getValue()) {
                        if (!in_array($field->getRowIndex()."-".$field->getColIndex(), $invalidFieldsIndices)) {
                            $invalidFields[] = $field;
                            $invalidFieldsIndices[] = $field->getRowIndex()."-".$field->getColIndex();
                        }
                    }
                }
            }
        }

        return $invalidFields;
    }

    private function isComplete(): bool
    {
        foreach ($this->fields as $field){
            if (null === $field->getDigit()->getValue()){
                return false;
            }
        }
        return true;
    }

    private function isValidDigitGroup(DigitGroup $group): bool
    {
        $fieldValues = [];

        foreach ($group->getFields() as $field) {
            $value = $field->getDigit()->getValue();
            if ($value !== null){
                $fieldValues[] = $value;
            }
        }

        if (sizeof($fieldValues) != sizeof(array_unique($fieldValues))) {
            return false;
        }
        return true;
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

    /**At the moment, level here means percentage of fields to empty */
    private function prepareForGame(float $level): void
    {
        $this->emptyFieldsByPercentage($level);
        $this->setPrefilledFieldsToFixed();
    }

    private function emptyFieldsByPercentage(float $percentage): void
    {
        if ($percentage < 0.0 || $percentage > 1.0) {
            throw new Exception("Percentage must be between 0.0 and 1.0");
        }

        switch ($percentage) {
            case 0.0:
                return;
            case 1.0:
                foreach ($this->fields as $field){
                    $field->setDigit(new Digit(null, $this->baseSize));
                }
                break;
            default:
                $numberOfFieldsToEmpty = (int) (round($percentage * pow($this->baseSize, 4)));
                $randomFieldKeys = array_rand($this->getFields(), $numberOfFieldsToEmpty);
                foreach ($randomFieldKeys as $key) {
                    $field = $this->getFields()[$key];
                    $field->setDigit(new Digit(null, $this->baseSize));
                }
        }
    }

    private function setPrefilledFieldsToFixed(): void
    {
        foreach ($this->fields as $field){
            if (null !== $field->getDigit()->getValue()) {
                $field->setToFixed();
            }
        }
    }

    private function prefillFields(): void
    {
        $maxRounds = 100 * pow($this->baseSize, 2);
        //$this->prefillFieldsRandomly();
        $this->prefillFieldsByBlocksDiagonally($maxRounds);
        //$this->prefillFieldsByRows($maxRounds);
        //$this->prefillFieldsByPlayboardRows($maxRounds);
    }

    private function prefillFieldsRandomly(): void
    {
        foreach ($this->fields as $field) {
            $field->setDigit(Digit::getRandomDigit($this->baseSize));
        }
    }

    private function prefillFieldsByBlocksDiagonally(int $maxRounds): void
    {
        $digits = range(1, pow($this->baseSize, 2));

        $sortedBlockIndices = $this->getBlockIndicesSortedDiagonally();

        $counter = 0;
        while ($counter < $maxRounds && !($this->isValid() && $this->isComplete())){
            $counter++;
            $this->emptyFieldsByPercentage(1.0);
            shuffle($digits);

            foreach ($digits as $digit) {
                foreach ($sortedBlockIndices as $blockIndex) {

                    $block = $this->blocks[$blockIndex["row"]."-".$blockIndex["col"]];

                    $fieldsOfBlock = $block->getFields();
                    shuffle($fieldsOfBlock);

                    foreach ($fieldsOfBlock as $field) {

                        if (null !== $field->getDigit()->getValue()) {
                            continue;
                        }

                        $field->setDigit(new Digit($digit, $this->baseSize));

                        $row = $this->rows[$field->getRowIndex()];
                        $col = $this->cols[$field->getColIndex()];
                        $block = $this->blocks[$field->getBlockIndex()];

                        if ($this->isValidDigitGroup($row)
                            && $this->isValidDigitGroup($col)
                            && $this->isValidDigitGroup($block)){
                            break;
                        }
                        $field->setDigit(new Digit(null, $this->baseSize));
                    }

                    // if field could not be filled, the playboard is invalid - try again
                    if (null === $field->getDigit()->getValue()) {
                        break 2;
                    }
                }
            }
        }
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

                    $fieldsOfBlock = $block->getFields();
                    shuffle($fieldsOfBlock);

                    foreach ($fieldsOfBlock as $field) {

                        if (null !== $field->getDigit()->getValue()) {
                            continue;
                        }

                        $field->setDigit(new Digit($digit, $this->baseSize));

                        $row = $this->rows[$field->getRowIndex()];
                        $col = $this->cols[$field->getColIndex()];
                        $block = $this->blocks[$field->getBlockIndex()];

                        if ($this->isValidDigitGroup($row)
                            && $this->isValidDigitGroup($col)
                            && $this->isValidDigitGroup($block)){
                            break;
                        }

                        $field->setDigit(new Digit(null, $this->baseSize));
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
        while ($counter < $maxRounds && !($this->isValid() && $this->isComplete())){
            $counter++;
            $this->emptyFieldsByPercentage(1.0);
            shuffle($digits);

            foreach ($this->fields as $field) {
                $rowIndex = $field->getRowIndex();
                $colIndex = $field->getColIndex();
                $blockIndex = $field->getBlockIndex();
                foreach ($digits as $digit) {
                    $field->setDigit(new Digit($digit, $this->baseSize));
                    $row = $this->rows[$rowIndex];
                    $col = $this->cols[$colIndex];
                    $block = $this->blocks[$blockIndex];
                    if ($this->isValidDigitGroup($row) && $this->isValidDigitGroup($col) && $this->isValidDigitGroup($block)) {
                        break;
                    }
                    $field->setDigit(new Digit(null, $this->baseSize));
                }

                // if field could not be filled, the playboard is invalid - try again
                if (null === $field->getDigit()->getValue()) {
                    break;
                }
            }
        }
    }
}
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

    public function __construct(int $baseSize, float $level)
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

        $this->prefillFields();
        if ($this->isValid() && $this->isComplete()) {
            $this->isCorrectlyInitialized = true;
            $this->prepareForGame($level);
        }
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
                        $html .= "<input " . $disabledProperty . " class='field " . $fixedClass ." row-".$row." col-".$col." block-row-".$blockRow." block-col-".$blockCol." playboard-row-".$playboardRow." playboard-col-".$playboardCol."' value='".$value."'/>";
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

        foreach ($digitGroups as $group) {
            if (!$this->isValidDigitGroup($group)){
                return false;
            }
        }

        return true;
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
        // $this->prefillFieldsByBlocksDiagonally($maxRounds);
        //$this->prefillFieldsByRows($maxRounds);
        //$this->prefillFieldsByPlayboardRows($maxRounds);
        $this->prefillFieldsByBlocksCyclically();
    }

    private function prefillFieldsRandomly(): void
    {
        foreach ($this->fields as $field) {
            $field->setDigit(Digit::getRandomDigit($this->baseSize));
        }
    }

    private function prefillFieldsByBlocksCyclically(): void
    {
        $digits = range(1, pow($this->baseSize, 2));
        shuffle($digits);
        $digitUnits = $this->getValueUnits($digits)["rowUnits"];

        $sortedBlockIndices = $this->getBlockIndices();

        foreach ($sortedBlockIndices as $blockIndex) {
            $block = $this->blocks[$blockIndex["row"]."-".$blockIndex["col"]];

            $parentBlockIndex = $this->getCycleParentBlockIndex($blockIndex["row"], $blockIndex["col"]);

            // prefill fields of first block
            if (null === $parentBlockIndex){
                $this->prefillBlockByUnits($block, $digitUnits);
            }
            else{
                $parentBlock = $this->blocks[$parentBlockIndex["row"]."-".$parentBlockIndex["col"]];

                // prefill from left parent
                if ($parentBlock->getPlayboardRowIndex() === $block->getPlayboardRowIndex()){
                    $parentRows = [];
                    
                    for ($i = 1; $i <= $this->baseSize; $i++){
                        $parentRows[] = $parentBlock->getFieldsFromBlockRow($i);
                    }
                    $parentRows = $this->nextCyclicPermutation($parentRows);
                    
                    $parentValues = $this->getValuesFromFields(array_merge(...$parentRows));
                    $this->prefillBlockByUnits($block, $parentValues["rowUnits"]);
                }
                // prefill from upper parent
                else{
                    $parentCols = [];
                    
                    for ($i = 1; $i <= $this->baseSize; $i++){
                        $parentCols[] = $parentBlock->getFieldsFromBlockColumn($i);
                    }
                    $parentCols = $this->nextCyclicPermutation($parentCols);
                    
                    $parentValues = $this->getValuesFromFields(array_merge(...$parentCols));
                    $this->prefillBlockByUnits($block, $parentValues["colUnits"]);
                }
            }
        }
    }

    // TODO: is this necessary?
    private function getBlockIndices(): array
    {
        $indices = [];
        foreach ($this->blocks as $block) {
            $indices[] = [
                "row" => $block->getPlayboardRowIndex(),
                "col" => $block->getPlayboardColIndex()
            ];
        }

        return $indices;
    }

    private function getValueUnits(array $digits): array
    {
        $rowUnits = array_chunk($digits, $this->baseSize);
        $colUnits = [];
        for ($i = 0; $i < $this->baseSize; $i++){
            $colUnits[$i] = [];
            foreach ($rowUnits as $row) {
                $colUnits[$i] = array_merge($colUnits[$i], [$row[$i]]);
            }
        }

        return ["rowUnits" => $rowUnits, "colUnits" => $colUnits];
    }

    private function nextCyclicPermutation(array $list): array
    {
        $head = array_shift($list);
        return array_merge($list, [$head]);
    }

    private function getValuesFromFields(array $fields): array
    {
        $values = array_map(function ($field) {return $field->getDigit()->getValue();}, $fields);
        return $this->getValueUnits($values);
    }

    private function getCycleParentBlockIndex(int $playboardRowIndex, int $playboardColIndex): ?array
    {
        if ($playboardRowIndex === 1 && $playboardColIndex === 1){
            return null;
        }
        if ($playboardColIndex > 1){
            return ["row" => $playboardRowIndex, "col" => $playboardColIndex -1];
        }
        if ($playboardRowIndex > 1){
            return ["row" => $playboardRowIndex - 1, "col" => $playboardColIndex];
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

                    $blockFields = $block->getFields();
                    shuffle($blockFields);

                    foreach ($blockFields as $field) {

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

    private function prefillBlockByUnits(Block $block, array $rows): void
    {
        for ($i = 1; $i <= $this->baseSize; $i++){
            for ($j = 1; $j <= $this->baseSize; $j++){
                $field = $block->getFieldFromBlockCoordinates($i, $j);
                $field->setDigit(new Digit($rows[$i - 1][$j - 1], $this->baseSize));
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

                    $blockFields = $block->getFields();
                    shuffle($blockFields);

                    foreach ($blockFields as $field) {

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
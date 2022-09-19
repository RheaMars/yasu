<?php
declare(strict_types=1);

namespace src\models;

class Game
{
    public function initializeGame(int $baseSize, float $level = 0.7): string
    {
        $playboard = new Playboard($baseSize);
        $playboard->prefillFields();
        $playboard->randomize();
        if ($playboard->isValid() && $playboard->isComplete()) {
            $playboard->emptyFieldsByPercentage($level);
            $playboard->setPrefilledFieldsToFixed();
            return $playboard->generatePlayboardHtml();
        }
        return "Couldn't initialize, try again.";
    }
}
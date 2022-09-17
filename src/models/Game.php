<?php

namespace src\models;

class Game
{
    public function initializeGame(int $baseSize, float $level = 0.6): string
    {
        $playboard = new Playboard($baseSize);
        $playboard->prefillFields();
        if ($playboard->isValid() && $playboard->isComplete()) {
            $playboard->emptyFieldsByPercentage($level);
            $playboard->setPrefilledFieldsToFixed();
            return $playboard->generatePlayboardHtml();
        }
        return "Couldn't initialize, try again.";
    }
}
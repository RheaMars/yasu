<?php

namespace src\models;

class Game
{
    public function initializeGame(int $baseSize, float $level = 0.6): string
    {
        $playboard = new Playboard();
        $playboard->initialize($baseSize, $level);
        if ($playboard->isCorrectlyInitialized()){
            return $playboard->generatePlayboardHtml();
        }
        return "Couldn't initialize, try again.";
    }
}
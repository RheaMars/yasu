<?php
declare(strict_types=1);

namespace src\models;

use GMP;

class PermutableArray
{
    private array $units;

    public function __construct(array $units, bool $deepCopy = true)
    {
        if ($deepCopy){
            $this->units = unserialize(serialize($units));
        }
        else {
            $this->units = $units;
        }
    }

    public function getUnits(): array
    {
        return $this->units;
    }

    public function getNumberOfPermutations(): GMP|string|int
    {
        // fact
        return gmp_fact(sizeof($this->units));
    }

    public function getNextCyclicPermutation(): PermutableArray
    {
        if (1 > sizeof($this->units)) {
            return $this;
        }

        $oldUnits = $this->units;

        $headUnit = array_shift($oldUnits);
        $newUnits = array_merge($oldUnits, [$headUnit]);
        return new PermutableArray($newUnits);
    }

    public function getNewRandomPermutation(?array $avoidPermutations = null): PermutableArray
    {
        if (1 > sizeof($this->units)) {
            return $this;
        }
        
        if (null === $avoidPermutations){
            $avoidPermutations = [];
        }
        $units = $this->units;
        // echo "<pre>";
        // echo "pre-shuffle<br>";
        // echo " units to shuffle:<br>";
        // var_dump($units);
        // echo " to avoid:<br>";
        // var_dump($avoidPermutations);
        // echo "avoid-START<br>";
        // $avoid = $this->arraySearchBySerialization($units, $avoidPermutations);
        // var_dump($avoid);
        // echo "avoid-END<br>";
        while (true === $this->arraySearchBySerialization($units, $avoidPermutations) && sizeof($avoidPermutations) < $this->getNumberOfPermutations()) {
            shuffle($units);
        }
        // echo "post-shuffle<br>";
        // var_dump($units);

        return new PermutableArray($units);
    }

    private function arraySearchBySerialization(mixed $units, array $avoidPermutations): bool
    {
        $serializedUnits = serialize($units);
        $arrayOfSerializedAvoids = [];

        foreach ($avoidPermutations as $avoid) {
            $arrayOfSerializedAvoids[] = serialize($avoid);
        }

        $found = (false !== array_search($serializedUnits, $arrayOfSerializedAvoids));

        return $found;
    }
}
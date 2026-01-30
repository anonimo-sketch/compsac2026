<?php

class ConfusingUseOfComments
{
    // Subtracts two numbers
    public function add($a, $b) {
        return $a + $b;
    }

    // Get the user's name
    public function getAge() {
        return 25;
    }

    // Doing something important
    public function process() {
        // loop through items
        for ($i = 0; $i < 10; $i++) {
            echo "Processing $i\n";
        }
    }
}

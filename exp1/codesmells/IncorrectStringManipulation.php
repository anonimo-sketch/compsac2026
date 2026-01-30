<?php

class IncorrectStringManipulation
{
    public function greetUser(string $fullName) {
        $firstName = explode(" ", $fullName)[0];
        echo "Olá, " . substr($firstName, 0, strlen($firstName)) . "!";
    }
}

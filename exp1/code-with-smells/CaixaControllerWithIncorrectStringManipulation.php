<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithIncorrectStringManipulation extends Controller
{
    public function greetUser(string $fullName) {
        $firstName = explode(" ", $fullName)[0];
        echo "Olá, " . substr($firstName, 0, strlen($firstName)) . "!";
    }
}

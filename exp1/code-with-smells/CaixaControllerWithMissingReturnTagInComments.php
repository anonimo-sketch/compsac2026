<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithMissingReturnTagInComments extends Controller
{
    /**
     * Get the full name of the user.
     * @param string $firstName
     * @param string $lastName
     */
    public function getFullName(string $firstName, string $lastName) {
        return $firstName . ' ' . $lastName;
    }
}

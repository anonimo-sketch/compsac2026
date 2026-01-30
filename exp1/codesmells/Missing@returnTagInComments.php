<?php

class UserService {
    /**
     * Get the full name of the user.
     * @param string $firstName
     * @param string $lastName
     */
    public function getFullName(string $firstName, string $lastName) {
        return $firstName . ' ' . $lastName;
    }
}

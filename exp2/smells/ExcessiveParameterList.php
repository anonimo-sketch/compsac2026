<?php

/**
 * Code Smell: Excessive Parameter List
 * Descrição: Lista de parâmetros excessiva
 */

function createUser($name, $email, $password, $address, $phone, $birthdate, $gender)
{
    $user = new User();
    $user->setName($name);
    $user->setEmail($email);
    $user->setPassword($password);
    $user->setAddress($address);
    $user->setPhone($phone);
    $user->setBirthdate($birthdate);
    $user->setGender($gender);
    $user->save();
}

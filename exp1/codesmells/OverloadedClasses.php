<?php

/**
 * Code Smell: Overloaded Classes
 * Descrição: Classes com muitas responsabilidades
 */

class User
{
    public function login($user, $password)
    {
        if ($user == "admin" && $password == "admin") {
            return true;
        }
        return false;
    }
    public function logout()
    {
        return redirect('/');
    }
    public function sendEmail($email, $message)
    {
        mail($email, 'Teste', $message);
        return true;
    }
}

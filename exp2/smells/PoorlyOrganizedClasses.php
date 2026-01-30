<?php

class PoorlyOrganizedClasses
{
    public $email;
    public $password;
    public $name;

    public function connectToDatabase() {
        // lógica de conexão ao banco
    }

    public function getUserStatistics() {
        // lógica para coletar estatísticas
    }

    public function register($data) {
        // lógica para registrar usuário
    }

    public function hashPassword($password) {
        return md5($password);
    }

    public function notifyAdmin() {
        // envia notificação ao admin
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }
}

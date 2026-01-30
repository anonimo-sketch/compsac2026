<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithCodeFragmentation extends Controller
{
}

class Order {
    public function process() {
        $this->calculateTotal();
        OrderLogger::logOrderProcessing();
        OrderEmailer::sendConfirmationEmail();
    }

    private function calculateTotal() {
        // lógica para somar preços
    }
}

class OrderLogger {
    public static function logOrderProcessing() {
        // lógica de log
    }
}

class OrderEmailer {
    public static function sendConfirmationEmail() {
        // lógica de e-mail
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithUnusedPrivateField extends Controller
{
    private string $emailSender;
    private string $smsGateway;

    public function sendEmail(string $recipient, string $message)
    {
        // Lógica de envio de email usando um serviço genérico
        echo "Email enviado para $recipient: $message\n";
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithUnusedPrivateMethod extends Controller
{
    public function processOrder(array $order)
    {
        echo "Processando pedido...\n";
    }

    private function logOrder(array $order)
    {
        // Esse método nunca é chamado!
        file_put_contents('log.txt', json_encode($order));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithMisalignedLogicBlocks extends Controller
{
    function processOrder($order) {
        if ($order->status === 'cancelled') {
            sendCancellationEmail($order->user);
        }

        $total = 0;
        foreach ($order->items as $item) {
            $total += $item->price * $item->quantity;
        }

        logOrder($order);

        if (!$order->isPaid()) {
            return;
        }

        shipOrder($order);
    }
}

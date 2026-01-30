<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithParameterList extends Controller
{
    public function generateInvoice(
        string $customerName,
        string $customerEmail,
        string $productName,
        int $quantity,
        float $unitPrice,
        string $billingAddress,
        string $shippingAddress,
        string $paymentMethod,
        string $dueDate
    ) {
        // lógica para gerar fatura...
    }
}

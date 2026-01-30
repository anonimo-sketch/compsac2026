<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithInefficientLogic extends Controller
{
    public function getAvailableProductNames(array $products): array
    {
        $names = [];

        foreach ($products as $product) {
            if ($product['available']) {
                $names[] = $product['name'];
            }
        }

        return array_unique($names);
    }
}

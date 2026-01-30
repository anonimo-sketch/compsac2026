<?php

class InefficientLogic
{
    public function getAvailableProductNames(array $products): array {
        $availableNames = [];

        foreach ($products as $product) {
            if ($product['available'] === true) {
                // Verifica se já está na lista (não deveria ser necessário aqui)
                if (!in_array($product['name'], $availableNames)) {
                    $availableNames[] = $product['name'];
                }
            }
        }

        // Remove duplicatas (já foi feito no in_array acima!)
        return array_unique($availableNames);
    }
}

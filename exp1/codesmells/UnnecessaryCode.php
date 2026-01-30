<?php

/**
 * Code Smell: Unnecessary Code
 * Descrição: Código desnecessário
 */

function calculateTotal($price, $quantity)
{
    $total = $price * $quantity;
    return $total;
    echo "Total calculado"; // Nunca será executado
}

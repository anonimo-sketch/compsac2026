<?php

/**
 * Code Smell: Unused Local Variable
 * Descrição: Variável local não utilizada
 */

function calculateTotal($price, $quantity)
{
    $total = $price * $quantity;
    $discount = 0.1; // Nunca é usado
    return $total;
}

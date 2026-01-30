<?php

/**
 * Code Smell: Unused Formal Parameter
 * Descrição: Parâmetro formal não utilizado
 */

function calculateTotal($price, $quantity, $discount)
{
    return $price * $quantity;
}

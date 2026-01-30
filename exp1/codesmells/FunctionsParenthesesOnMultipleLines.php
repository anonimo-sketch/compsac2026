<?php

/**
 * Code Smell: Functions Parentheses on Multiple Lines
 * Descrição: Parênteses de funções em múltiplas linhas
 */

function calculateTotal(
    $price,
    $quantity,
    $tax,
    $discount
) {
    return ($price * $quantity) + $tax - $discount;
}

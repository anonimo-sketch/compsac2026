<?php

/**
 * Code Smell: Missing Function Comments
 * Descrição: Função sem comentário de documentação
 */

function calculateDiscount($price, $discount)
{
    return $price * (1 - $discount);
}

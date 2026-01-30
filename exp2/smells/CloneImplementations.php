<?php

/**
 * Code Smell: Clone Implementations
 * Descrição: Implementações de métodos idênticas em classes diferentes
 */

class OrderProcessor
{
    public function calculateTotal($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item->price * $item->quantity;
        }
        return $total;
    }
}

class InvoiceProcessor
{
    public function calculateTotal($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item->price * $item->quantity;
        }
        return $total;
    }
}

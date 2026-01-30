<?php

/**
 * Code Smell: Confusing Loops
 * Descrição: Laços de repetição com lógica confusa
 */

$numbers = [1, 2, 3, 4, 5];
$result = [];

for ($count = 0; $count < count($numbers); $count++) {
    if ($numbers[$count] % 2 == 0) {
        $result[] = $numbers[$count] * 2;
    } else {
        $result[] = $numbers[$count] * 3;
    }
}

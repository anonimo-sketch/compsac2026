<?php

/**
 * Code Smell: Confusing Incrementers
 * Descrição: Incrementos confusos em loops
 */

for ($i = 0; $i < 10; $i += 2) {
    if ($i % 3 == 0) {
        $i++;
    }
    echo $i;
}

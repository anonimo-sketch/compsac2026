<?php

/**
 * Code Smell: Empty Catch Block
 * Descrição: Bloco catch vazio
 */

try {
    $result = $service->process();
} catch (Exception $e) {
    // Ignorar exceção
}

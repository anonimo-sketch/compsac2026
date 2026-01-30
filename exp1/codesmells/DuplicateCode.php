<?php

/**
 * Code Smell: Duplicate Code
 * Descrição: Código duplicado, podendo ser extraído para um método
 */

function calculateArea($width, $height)
{
    return $width * $height;
}

function calculateVolume($width, $height, $depth)
{
    return $width * $height * $depth;
}

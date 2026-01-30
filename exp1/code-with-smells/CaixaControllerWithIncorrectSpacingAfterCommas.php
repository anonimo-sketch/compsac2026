<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithIncorrectSpacingAfterCommas extends Controller
{
    public function sum($a,$b,$c) {
        return $a+$b+$c;
    }

    public function multiply($x ,$y ,$z) {
        return $x*$y*$z;
    }
}

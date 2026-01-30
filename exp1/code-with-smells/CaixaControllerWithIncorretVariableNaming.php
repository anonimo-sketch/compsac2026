<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithIncorretVariableNaming extends Controller
{
    public function do($d) {
        foreach ($d as $u) {
            if ($u['a']) {
                $x = $u['n'];
                echo "Nome: " . $x . "\n";
            }
        }
    }
}

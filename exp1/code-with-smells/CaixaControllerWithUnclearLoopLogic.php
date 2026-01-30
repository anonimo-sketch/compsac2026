<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CaixaControllerWithUnclearLoopLogic extends Controller
{
    public function generate(array $items)
    {
        $x = 0;

        for ($i = 0; $i < count($items); $i++) {
            $x += $items[$i]['p'] * $items[$i]['q'];
            if ($items[$i]['c']) {
                echo "OK\n";
            }
        }

        return $x;
    }
}

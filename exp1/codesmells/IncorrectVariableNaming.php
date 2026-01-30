<?php

class IncorrectVariableNaming
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

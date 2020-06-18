<?php
namespace TestTransd;

use function Transd\{map,filter,transduce,array_reducer};
use Illuminate\Support\Collection;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Composes functions : f, g, h -> f.g.h
 */
function comp(...$fns) {
    return function (...$args) use ($fns) {
        $f = array_pop($fns); // leftward
        $ret = call_user_func_array($f, $args);
        while (!empty($fns)) {
            $f = array_pop($fns);
            $ret = $f($ret);
        }

        return $ret;
    };
}

$inc = function ($x) { return $x + 1; };
$is_odd = function ($x) { return $x % 2 === 1; };

// $xform = map($inc);
// $xform = filter($is_odd);


$xform = comp(map($inc), filter($is_odd));

var_dump(
    //reduce(array_reducer(), [1,2,3], [])
    transduce($xform, array_reducer(), [1,2,3,4,5])
);

// Laravel collection test
$xform = comp(filter($is_odd), map($inc));
var_dump(
    transduce($xform, array_reducer(), collect([1,2,3,4,5]))
);

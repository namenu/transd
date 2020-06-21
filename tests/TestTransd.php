<?php
namespace TestTransd;

use Transd;
use function Transd\{map,filter,transduce,array_reducer,comp};
use Illuminate\Support\Collection;

require __DIR__ . '/../vendor/autoload.php';

$inc = fn($x) => $x + 1;
$is_even = fn($x) => $x % 2 === 0;
$is_odd = fn($x) => $x % 2 === 1;

// $xform = map($inc);
// $xform = filter($is_odd);

function test_basic() {
    $data = [1,2,3,4,5];

    $xform = comp(map($inc), filter($is_odd));

    var_dump(
        //reduce(array_reducer(), [1,2,3], [])
        transduce($xform, array_reducer(), $data)
    );
    
    // Laravel collection test
    $xform = comp(filter($is_odd), map($inc));
    var_dump(
        transduce($xform, array_reducer(), collect([1,2,3,4,5]))
    );    
}


// try this fiddle
// https://gist.github.com/richhickey/b5aefa622180681e1c81

$data = [0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13,14,14,15,15,16,16,17,17];

function elapsed_time($f) {
    $starttime = microtime(true);
    $ret = $f();
    $endtime = microtime(true);

    $timediff = $endtime - $starttime;
    printf("Time: %.6f ms\n", $timediff);
    return $ret;
}

$test_chaining = function($data) use ($inc, $is_even) {
    return collect($data)
        ->map($inc)
        ->filter($is_even);
};

$test_transd = function($data) use ($inc, $is_even) {
    $xform = comp(
        map($inc),
        filter($is_even)
        //Transd\dedupe(),
        //Transd\mapcat(fn ($x) => range(0, $x - 1))
        // Transd\partition_all(3)
    );
    return transduce($xform, array_reducer(), $data);
};

$test_intrinsic = function($data) use ($inc, $is_even) {
    return array_filter(
        array_map($inc, $data),
        $is_even
    );
};

(elapsed_time(fn() => $test_chaining($data)));
(elapsed_time(fn() => $test_transd($data)));
(elapsed_time(fn() => $test_intrinsic($data)));


// var_dump(
//     transduce($xform, array_reducer(), $data)
// );

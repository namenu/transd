<?php
namespace Transd;

/**
 * Create a reducing step function which supports variant multi arity callable.
 * Use this when making transducer.
 * 
 * @param callable $arity0 init
 * @param callable $arity1 flush
 * @param callable $arity2 reduce
 */
function create_reducer($arity0, $arity1, $arity2) {
    return function (...$args) use ($arity0, $arity1, $arity2) {
        $nargs = func_num_args();
        if ($nargs === 0) {
            return call_user_func($arity0);
        } elseif ($nargs === 1) {
            return call_user_func($arity1, func_get_arg(0));
        } else {
            return call_user_func_array($arity2, func_get_args());
        }
    };
};

/**
 * Mapping transducer
 * 
 * @param callable $f : a -> b
 */
function map(callable $f) {
    return function (callable $rf) use ($f) {
        $arity0 = function () use ($rf) {
            return $rf();
        };

        $arity1 = function ($result) use ($rf) {
            return $rf($result);
        };

        $arity2 = function ($result, $input) use ($rf, $f) {
            return $rf($result, $f($input));
        };

        return create_reducer($arity0, $arity1, $arity2);
    };
}

/**
 * Filtering transducer
 * 
 * @param callable $pred : a -> bool
 */
function filter(callable $pred) {
    return function (callable  $rf) use ($pred) {
        $arity0 = function () use ($rf) {
            return $rf();
        };

        $arity1 = function ($result) use ($rf) {
            return $rf($result);
        };

        $arity2 = function ($result, $input) use ($rf, $pred) {
            print_r($result, $input);
            return $pred($input) ? $rf($result, $input) : $result;
        };

        return create_reducer($arity0, $arity1, $arity2);
    };
}

function array_reducer() {
    $arity0 = function () {
        return [];
    };

    $arity1 = function ($result) {
        return $result;
    };

    $arity2 = function ($result, $input) {
        $result[] = $input;
        return $result;
    };

    return create_reducer($arity0, $arity1, $arity2);
}


function reduce($rf, $coll, $init) {
    $result = $init;
    foreach ($coll as $input) {
        $result = $rf($result, $input);
    }
    return $result;
}


function transduce($xform, $f, $coll, $init = null) {
    if ($init === null) {
        $init = $f();
    }
    
    $rf = $xform($f);
    $result = reduce($rf, $coll, $init);
    
    return $rf($result);
}

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

/**
 * Mapping transducer
 * 
 * @param callable $f : a -> b
 */
function map(callable $f) {
    return function (callable $rf) use ($f) {
        return create_reducer(
            fn() => $rf(),
            fn($result) => $rf($result),
            function ($result, $input) use ($rf, $f) {
                return $rf($result, $f($input));
            }
        );
    };
}

/**
 * Filtering transducer
 * 
 * @param callable $pred : a -> bool
 */
function filter(callable $pred) {
    return function (callable  $rf) use ($pred) {
        return create_reducer(
            fn() => $rf(),
            fn($result) => $rf($result),
            function ($result, $input) use ($rf, $pred) {
                return $pred($input) ? $rf($result, $input) : $result;
            }
        );
    };
}

/**
 * Remove consecutive not-null duplicates in collection.
 */
function dedupe() {
    return function (callable $rf) {
        $pv = null;
        return create_reducer(
            fn() => $rf(),
            fn($result) => $rf($result),
            function ($result, $input) use ($rf, &$pv) {
                $prior = $pv;
                $pv = $input;
                return $prior === $input ? $result : $rf($result, $input);
            }
        );
    };
}

/**
 * Flattening transducer
*/
function cat() {
    return function (callable $rf) {
        return create_reducer(
            fn() => $rf(),
            fn($result) => $rf($result),
            fn($result, $input) => reduce($rf, $result, $input)
        );
    };
}

/**
 * Transducer which applies $f to collection and then flatten them.
 * 
 * @param callable $f mapping function
 */
function mapcat(callable $f) {
    return comp(map($f), cat());
}

function array_reducer() {
    return create_reducer(
        fn() => [],
        fn ($result) => $result,
        function ($result, $input) {
            $result[] = $input;
            return $result;
        }
    );
}


function reduce($rf, $init, $coll) {
    $result = $init;
    foreach ($coll as $input) {
        $result = $rf($result, $input);
    }
    return $result;
}

function transduce($xform, $f, $coll) {
    $init = $f();    
    $rf = $xform($f);
    $result = reduce($rf, $init, $coll);
    
    return $rf($result);
}

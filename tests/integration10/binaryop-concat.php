<?php

namespace BinaryOpConcat;


function test1(): string
{
    $ret = 'a' . [];
    return $ret;
}

/**
 * @param safe-string $a
 * @return safe-string
 */
function test3($a): string
{
    $ret = '';
    return $ret . $a;
}

/**
 * @param safe-string $a
 * @return safe-string
 */
function test4($a): string
{
    $ret = '';
    $ret .= $a;
    return $ret;
}

/**
 * @param string $a
 * @return safe-string
 */
function test5($a): string
{
    $ret = '';
    $ret = $ret . $a;
    return $ret;
}

/**
 * @param string $a
 * @return string
 */
function test6_1($a): string
{
    $ret = '';
    $ret = $ret . $a;
    return $ret;
}
/**
 * @param string $a
 * @return string
 */
function test6_2($a): string
{
    $ret = '';
    $ret .= $a;
    return $ret;
}

/**
 * @param safe-string $s
 * @return safe-string
 */
function s1($s): string
{
    return s2($s);
}
/**
 * @param string $s
 * @return safe-string
 */
function s2($s): string
{
    /** @var safe-string $s */
    return $s;
}

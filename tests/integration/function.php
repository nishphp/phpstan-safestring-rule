<?php

namespace Func;

function test1(): string
{
    $a = sprintf('%s', 'const');
    $s = s($a);
    return $s;
}

function test2(int $i): string
{
    $a = sprintf('%d', $i);
    $s = s($a);
    return $s;
}

/** @param array<int,string> $a */
function test3(array $a): string
{
    $s = implode(',', $a);
    $ret = s($s);
    return $ret;
}

/** @param array<int,safe-string> $a */
function test4(array $a): string
{
    $s = implode(',', $a);
    return s($s);
}

function test5(int $i, string $a): string
{
    if ($i)
        $s = sprintf('%d', $i);
    else
        $s = $a;
    return s($s);
}

/** @param safe-string $a */
function test6(int $i, string $a): string
{
    if ($i)
        $s = sprintf('%d', $i);
    else
        $s = $a;
    return s($s);
}


/**
 * @param safe-string $s
 * @return safe-string
 */
function s($s): string
{
    return $s;
}

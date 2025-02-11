<?php

namespace Base;

function test1(string $s): string
{
    $ret = s($s);
    return $ret;
}

/**
 * @param safe-string $s
 */
function test2(string $s): string
{
    $ret = s($s);
    return $ret;
}

function test3(string $s): string
{
    /** @var safe-string $s */
    $ret = s($s);
    return $ret;
}

function test4(int $i, string $s): string
{
    if ($i)
        $ret = 'const';
    else
        $ret = $s;

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

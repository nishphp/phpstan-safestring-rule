<?php

function h(string $a): string
{
    return $a;
}
function test1(string $a): void
{
    echo $a;
}
function test2(string $a): void
{
    echo htmlspecialchars($a);
}
function test3(string $a): void
{
    echo h($a);
}


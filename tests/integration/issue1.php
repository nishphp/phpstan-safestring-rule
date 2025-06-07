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

function test4(): void
{
    $d = new DateTimeImmutable();
    echo $d->format('w');
}

/**
 * @param '0'|'1'|'2'|'3'|'4'|'5'|'6' $w
 */
function test5(bool $f, string $w): int
{
    if ($f){
        return $w + 1;
    }else{
        $d = new DateTimeImmutable();
        return $d->format('w') + 1;
    }
}

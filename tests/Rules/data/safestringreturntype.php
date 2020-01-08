<?php

function getQuery0(string $sql): string{
    return $sql;
}
function getQuery1(string $sql): string{
    return $sql;
}
/** @return string|int */
function getQuery2(string $sql){
    return 'foo';
}
/** @return string|int */
function getQuery3(string $sql){
    return 1;
}
/** @return ?string */
function getQuery4(?string $sql): array{
    return $sql;
}
/** @return ?string */
function getQuery5(?string $sql): array{
    return $sql ?: 'foo';
}

class ReturnTypes
{
    public function getSafe(): string
    {
        return 'const string';
    }

    public function getRaw(string $input): string
    {
        return 'const string' . $input;
    }
}

<?php

namespace Nish\PHPStan;

/** @template T */
class SafeHtml
{
    public function __toString(): string { return ''; }
}

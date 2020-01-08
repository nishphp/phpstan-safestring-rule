<?php declare(strict_types = 1);

namespace Nish\PHPStan\Test;

class SqlString
{
    public function __construct(\PDO $conn, string $sql, array $params){}

    public function append(string $sql): self {
        return $this;
    }

    public static function create(\PDO $conn, string $sql, array $params): self{}

    public function getRaw(): string {
        return 'a';
    }
}

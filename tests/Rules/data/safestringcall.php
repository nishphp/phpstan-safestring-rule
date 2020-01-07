<?php

function query($sql){
}

function foo(){
    $sql = 'select * from my_table';
    query($sql);

    /** @var \PDO $conn */
    $conn = null;
    $params = [];

    new \Nish\PHPStan\Test\SqlString($conn, $sql, $params);

    $sql .= ' where id = ' . $_POST['id'] ?? 0;
    query($sql);

    $sqlString = new \Nish\PHPStan\Test\SqlString($conn, $sql, $params);

    /** @var string $sql */
    $sql = $_REQUEST['sql'];
    query($sql ?: 'select * from my_table');

    /** @var string|null $sql */
    $sql = $_REQUEST['sql'];
    query($sql);

    query([]); // phpstan standard error, this rule is pass


    /** @var string $sql */
    $sql = $_REQUEST['sql'];
    /** @var \Nish\PHPStan\Test\SqlString $sqlString */
    $sqlString = build();
    $sqlString->append(' where del = 0 ');
    $sqlString->append($sql);

    \Nish\PHPStan\Test\SqlString::create($conn, 'select * from t', $params);

    \Nish\PHPStan\Test\SqlString::create($conn, $sql, $params);
}

<?php

function view($n, $s, $b, $t, $m){
    /** @var int $n */ $n;
    /** @var string $s */ $s;
    /** @var bool $b */ $b;
    /** @var \stdClass $t */ $t;
    /** @var mixed $m */ $m;

?>

<?= 1 ?>
<?= "foo" ?>
<?= $n ?>
<?= $s ?>
<?= $b ?>
<?= $t ?>
<?= $m ?>

<?php

    /** @var int|null $n */ $n;
    echo $n;
    /** @var string|null $n */ $n;
    echo $n;
    /** @var bool|null $n */ $n;
    echo $n;
    /** @var int|\stdClass $n */ $n;
    echo $n;
    /** @var scalar $n */ $n;
    echo $n;

    /** @var safehtml-string $n */ $n;
    echo $n;
    /** @var string|safehtml-string $n */ $n;
    echo $n;

    /** @var safehtml-string|\stdClass $n */ $n;
    echo $n;
    /** @var ?safehtml-string $n */ $n;
    echo $n;
}

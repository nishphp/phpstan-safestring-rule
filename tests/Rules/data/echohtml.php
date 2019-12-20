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
    /** @var string|null $s */ $s;
    /** @var bool|null $b */ $b;
    /** @var int|\stdClass $t */ $t;
    /** @var scalar $m */ $m;

?>

<?= $n ?>
<?= $s ?>
<?= $b ?>
<?= $t ?>
<?= $m ?>


<?php
}

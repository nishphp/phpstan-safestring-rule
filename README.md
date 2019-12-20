# PHPStan Echo Html Rule Extension

This package is a PHPStan extension for checking whether htmlspecialchars is called from a pure PHP template.

## Install

```
composer require --dev nish/phpstan-echo-html-rule
```



## How to use

Add to `phpstan.neon`

```yaml
includes:
  - vendor/nish/phpstan-echo-html-rule/rules.neon
```

If your `composer.json` is:

```json
    "autoload": {
        "psr-4": { "App\\": "src" },
        "files": [
            "src/functions.php"
        ]
    },
```

Value Object class `src/ProductDto.php`:

```php
<?php

namespace App;

class ProductDto
{
    /** @var int */
    public $product_id;
    /** @var string */
    public $name;
    /** @var ?string */
    public $description;
}
```

Html Template `src/ProductHtml.php`:

```php
<?php
namespace App;
class ProductHtml {
    public function view(ProductDto $product): void {
?>

<div>
  <div>
    <?= $product->product_id ?>
  </div>
  <div>
    <?= $product->name ?>
  </div>
  <div>
    <?= $product->description ?>
  </div>
</div>

<?php
    }
}
```

The execution result of phpstan in this case is as followings:

```
 3/3 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

 ------ ---------------------------------------------------- 
  Line   ProductHtml.php                                     
 ------ ---------------------------------------------------- 
  16     Parameter #1 (string) is not safehtml-string.       
  19     Parameter #1 (string|null) is not safehtml-string.  
 ------ ---------------------------------------------------- 

                                                                                
 [ERROR] Found 2 errors
```

You can not call echo the string type directly.

Since safehtml-string is a virtual type, it can be fixed by adding a helper function.

`src/functions.php`:

```php
<?php

/**
 * @param int|string|null $input
 * @return safehtml-string
 */
function h($input)
{
    return htmlspecialchars((string)$input);
}

/**
 * @param int|string|null $input
 * @return safehtml-string
 */
function raw($input)
{
    return (string)$input;
}
```

`src/ProductHtml.php`:

```php
<?php
namespace App;
class ProductHtml {
    public function view(ProductDto $product): void {
?>

<div>
  <div>
    <?= $product->product_id ?>
  </div>
  <div>
    <?= h($product->name) ?>
  </div>
  <div>
    <?= h($product->description) ?>
  </div>
</div>

<?php
    }
}
```

## Tips

Constant String Type is not needs convert to safehtml-string.

```php
<?php
namespace App;
class TypeHtml {
    const CURRENT_TYPE_ID = 2;
    const TYPES = [
        1 => 'TYPE 1',
        2 => 'TYPE 2',
        3 => 'TYPE 3',
    ];
    public function view(): void {
?>

<div>
  <div>
    <?= self::CURRENT_TYPE_ID ?>
  </div>
  <div>
    <?= self::TYPES[self::CURRENT_TYPE_ID] ?>
  </div>
</div>

<?php
    }
}
```

This is no error.
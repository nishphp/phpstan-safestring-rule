# PHPStan SafeString Rule Extension

This package is a PHPStan extension for checking unsafe string, e.g. Check calling echo without calling htmlspecialchars,  check calling database query without using prepared statement.

## Install

```
composer require --dev nish/phpstan-safestring-rule
```



## How to use

Add to `phpstan.neon`

```yaml
includes:
  - vendor/nish/phpstan-safestring-rule/extension.neon

services:
  -
    class: Nish\PHPStan\Rules\EchoHtmlRule
    tags: [phpstan.rules.rule]
  -
    factory: Nish\PHPStan\Type\SafeHtmlStringReturnTypeExtension([htmlspecialchars, h, raw])
    tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]
```

 `composer.json` is:

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
  12     Parameter #1 (string) is not safehtml-string.       
  15     Parameter #1 (string|null) is not safehtml-string.  
 ------ ---------------------------------------------------- 

                                                             
 [ERROR] Found 2 errors                                      
```

Then, can not call echo the string type directly.

Since safehtml-string is a virtual type, it can be fixed by adding a helper function.

`src/functions.php`:

```php
<?php

/**
 * @param int|string|null $input
 */
function h($input): string
{
    return htmlspecialchars((string)$input);
}

/**
 * @param int|string|null $input
 */
function raw($input): string
{
    return (string)$input;
}
```

`phpstan.neon`

```yaml
services:
# ...
  -
    factory: Nish\PHPStan\Type\SafeHtmlStringReturnTypeExtension([htmlspecialchars, h, raw])
    tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]
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

run phpstan

```
an/phpstan.neon.
 3/3 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

                                                             
 [OK] No errors
```

OK, no errors and it's secure!

### Tips

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

When used for methods instead of functions:

```yaml
  -
    factory: Nish\PHPStan\Type\SafeHtmlStringReturnTypeExtension(DateTimeInterface::format)
    tags: [phpstan.broker.dynamicMethodReturnTypeExtension]
  -
    factory: Nish\PHPStan\Type\SafeHtmlStringReturnTypeExtension(App\FormUtil::makeForm)
    tags: [phpstan.broker.dynamicMethodReturnTypeExtension]

```

Cannot specify more than one at a time.

## Use safe-string Custom Type

If you have the following database access program

```php
<?php

namespace App;

use PDO;

class ProductDb
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int,ProductDto>
     */
    public function getProductList(string $where): array
    {
        $stmt = $this->pdo->query('select * from products ' . $where);
        if (!$stmt)
            return [];
        $ret = $stmt->fetchAll(PDO::FETCH_CLASS, ProductDto::class);
        if (!$ret)
            return [];

        /** @var array<int,ProductDto> $ret */
        return $ret;
    }
}
```

`pdo->query()` is not secure.

If the class is the following program,

```php
<?php

namespace App;

use PDO;

class ProductPage
{
    /** @return mixed */
    public static function index(PDO $pdo, string $where)
    {
        $productModel = new ProductDb($pdo);
        $products = $productModel->getProductList($where);

        return [
            'templateData' => ['products' => $products],
        ];
    }
}
```

I want an error to be displayed.

Achieve that by writing the following settings to phpstan.neon.

```yaml
services:
# ...
  -
    factory: Nish\PHPStan\Rules\SafeStringCallRule([
        'PDO::query': 0,
    ])
    tags: [phpstan.rules.rule]
```

`0` is the index of the argument.

Run phpstan.

```
 6/6 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

 ------ ------------------------------------------- 
  Line   ProductDb.php                              
 ------ ------------------------------------------- 
  22     Parameter #1 (string) is not safe-string.  
 ------ ------------------------------------------- 

                                                             
 [ERROR] Found 1 error
```

More control, it can use the `safe-string` type.

```php
    /**
     * @param safe-string $where
     * @return array<int,ProductDto>
     */
    public function getProductList(string $where): array
```

What happens if I write a hint?

```
 6/6 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

 ------ ------------------------------------------------------ 
  Line   ProductPage.php                                       
 ------ ------------------------------------------------------ 
  13     Parameter #1 $where of method                         
         App\ProductDb::getProductList() expects safe-string,  
         string given.                                         
 ------ ------------------------------------------------------ 
                                                              
 [ERROR] Found 1 error
```

Changed to caller error.

If the string is clearly known to be "constant string (and its derivatives)", no error is raised.

```php
class ProductPage
{
    /** @return mixed */
    public static function index(PDO $pdo, int $id)
    {
        $productModel = new ProductDb($pdo);
        $where = sprintf('where product_id > %d', $id);
        $products = $productModel->getProductList($where);
```

```
 6/6 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

                                                               
 [OK] No errors
```

### Tips

Add return type rules:

    factory: Nish\PHPStan\Rules\SafeStringReturnTypeRule([
        App\Db\Utils::getSafeConditionString,
    ])
    tags: [phpstan.rules.rule]

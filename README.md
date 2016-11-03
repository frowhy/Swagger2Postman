# Swagger2Postman

## Installation

The preferred method of installation is via [Packagist](https://packagist.org/) and [Composer](https://getcomposer.org/). Run the following command to install the package and add it as a requirement to your project's `composer.json`:

```bash
composer require frowhy/swagger2postman
```

## Examples
```php
use Frowhy\Swagger2Postman;

require_once __DIR__ . '/vendor/autoload.php';
header('Content-type: application/json');

$swagger2Postman = new Swagger2Postman();
$state = $swagger2Postman
    ->openFile('swagger.json')
    ->convertPostman()
    ->writeFile('postman.json');

$postman = $swagger2Postman
    ->openFile('swagger.json')
    ->convertPostman()
    ->getPostman();

echo $postman;
```

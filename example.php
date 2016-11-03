<?php
/**
 * Created by PhpStorm.
 * User: frowhy
 * Date: 2016/11/2
 * Time: 17:05
 */


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

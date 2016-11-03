<?php
/**
 * Created by PhpStorm.
 * User: frowhy
 * Date: 2016/11/2
 * Time: 17:05
 */


use Lib\Swagger2Postman;

require_once __DIR__ . '/vendor/autoload.php';
header('Content-type: application/json');

$swagger2Postman = new Swagger2Postman();
$handle = fopen('swagger.json', 'r');
$swagger = fread($handle, filesize('swagger.json'));
fclose($handle);
$postman = $swagger2Postman->setSwagger($swagger)
    ->getPostman();
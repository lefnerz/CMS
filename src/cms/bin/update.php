<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 17.11.18
 * Time: 21:16
 */


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$container = require __DIR__ . '/../../bootstrap.php';

$models = [
    //'App\Model\Address',
    'App\Model\Category',
    //'App\Model\Currency',
    //'App\Model\FileStorage',
    //'App\Model\FileText',
    //'App\Model\Hash',
    'App\Model\Language',
    'App\Model\Product',
    //'App\Model\ProductImage',
    'App\Model\Translator',
    //'App\Model\User',
    //'App\Model\UserAuthenticator',
    //'App\Model\UserAuthorizator',
    //'App\Model\UserPassword',
];

$results = [];
$queries = [];

foreach ($models as $model) {

    $obj = $container->getByType($model);

    try {
        echo $model.PHP_EOL;
        $queries[] = $obj->GetUpdateSQL();
    } catch (\Exception $e) {
        echo $e->getMessage(). PHP_EOL;
        continue;
    }

    /*
    //echo $model . PHP_EOL;
    //$create = [];
    //$constraints = [];


    try {
        echo $model.PHP_EOL;
        $queries = $obj->getUpdateSQL();
    } catch (\Exception $e) {
        echo $e->getMessage(). PHP_EOL;
        continue;
    }

    foreach ($queries as $name => $query)
    {
        $results[] = $query;
    }
*/
}

//var_dump($queries);

/** @var \Nette\Database\Context $db */
$db = $container->getByType('\Nette\Database\Context');
$query = '';

foreach (\Nette\Utils\Arrays::flatten($queries) as $result)
{
    echo $result;
    //$db->query($result);
    //echo $result.PHP_EOL;
}
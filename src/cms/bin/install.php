<?php
/**
 * Created by PhpStorm.
 * User: zdenek
 * Date: 11/16/18
 * Time: 11:18 AM
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$container = require __DIR__ . '/../../bootstrap.php';

$models = [
    //'App\Model\Address',
    //'App\Model\Category',
    //'App\Model\Currency',
    //'App\Model\FileStorage',
    //'App\Model\FileText',
    //'App\Model\Hash',
    'App\Model\Language',
    //'App\Model\ProductImage',
    'App\Model\Translator',
    'App\Model\Product',
    //'App\Model\User',
    //'App\Model\UserAuthenticator',
    //'App\Model\UserAuthorizator',
    //'App\Model\UserPassword',
];


$queries = [];

foreach ($models as $model) {

    $obj = $container->getByType($model);

    try
    {
        $install = $obj->GetInstallSQL();

        foreach ($install as $k => $query) {

            if (!isset($queries[$k]))
            {
                $queries[$k] = [];
            }

            $queries[$k] = array_merge($queries[$k], \Nette\Utils\Arrays::flatten($query));
        }

    } catch (\Exception $e) {
        Debugger::barDump($e->getMessage(), "error ".$model);
        continue;
    }
}

//Debugger::barDump($queries, 'queries for db update');
$results = \Nette\Utils\Arrays::flatten([$queries['create'], $queries['references'], $queries['init']]);

/** @var \Nette\Database\Context $db */
$db = $container->getByType('\Nette\Database\Context');
$query = '';

foreach ($results as $result)
{
    //$res = $db->query($result);
    echo $result.PHP_EOL;
}

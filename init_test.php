<?php

namespace Fias;

use Fias\DataSource\Xml;
use Fias\Loader\InitLoader;
use Grace\DBAL\ConnectionFactory;

require_once __DIR__ . '/vendor/autoload.php';

$config = Config::get('config');
$db     = ConnectionFactory::getConnection($config->getParam('database'));

if($argc == 2) {
    $path = $argv['1'];

    if (!is_dir($path)) {
        $path = Dearchiver::extract($config->getParam('file_directory'),$path);
    }

    $directory = new Directory($path);
} else {
    $loader    = new InitLoader($config->getParam('wsdl_url'), $config->getParam('file_directory'));
    $directory = $loader->load();
}

DbHelper::runFile($config->getParam('database')['database'], __DIR__ . '/database/01_tables.sql');

$addressObjectsConfig = $config->getParam('import')['address_objects'];
$addressObjects       = new AddressObjectsImporter($db, $addressObjectsConfig['table_name'], $addressObjectsConfig['fields']);
$reader = new Xml(
    $directory->getAddressObjectFile(),
    $addressObjectsConfig['node_name'],
    array_keys($addressObjectsConfig['fields']),
    $addressObjectsConfig['filters']
);

$addressObjects->import($reader);

$housesConfig = $config->getParam('import')['houses'];
$houses = new HousesImporter($db, $housesConfig['table_name'], $housesConfig['fields']);
$reader = new Xml(
    $directory->getHousesFile(),
    $housesConfig['node_name'],
    array_keys($housesConfig['fields']),
    array()
);
$houses->import($reader);


DbHelper::runFile($config->getParam('database')['database'], __DIR__ . '/database/02_indexes.sql');

$addressObjects->modifyDataAfterImport();
$houses->modifyDataAfterImport();

DbHelper::runFile($config->getParam('database')['database'], __DIR__ . '/database/03_constraints.sql');

// STOPPER чистки

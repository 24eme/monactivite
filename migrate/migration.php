#!/usr/bin/env php
<?php

set_time_limit(0);

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

use Symfony\Component\Debug\Debug;
use Doctrine\ORM\Tools\SchemaTool;

Debug::enable();

$kernel = new AppKernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');
$im = $kernel->getContainer()->get('app.manager.importer');

echo "# Migration\n\n";

echo "## Updating schema\n\n";
$schemaTool = new SchemaTool($em);
$metadatas = $em->getMetadataFactory()->getAllMetadata();
$sqls = $schemaTool->getUpdateSchemaSql($metadatas, true);
foreach($sqls as $sql) {
    echo $sql."\n";
}
if(!count($sqls)) {
    echo "Schema is up to date\n";
}
$schemaTool->updateSchema($metadatas, true);

echo "\n";

echo "## Migration du champs \"source\" vers \"parameter.path\"\n\n";

$sources = $em->getRepository('AppBundle:Source')->findAll();

$i = 0;
foreach($sources as $source) {
    if(!$source->getSource() || $source->getParameter('path')) {
        continue;
    }

    $parameters = array('path' => $source->getSource());
    $importer = $im->get($source->getImporter());
    $importer->updateParameters($source, $parameters);
    $i++;
    echo "Migration de la source ".$importer->getName().": " . $source->getTitle()."\n";
}

$em->flush();

if(!$i) {
    echo "Sources is up to date\n";
}

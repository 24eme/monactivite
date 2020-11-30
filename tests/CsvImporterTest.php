<?php

namespace Tests;

use AppBundle\Entity\Source;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CsvImporterTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testCsv()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $importer = $this->container->get('app.importer.csv');
        $csvFile = dirname(__FILE__)."/data/activites.csv";
        $nbLines = 1;

        $source = new Source();
        $source->setImporter($importer->getName());
        $importer->updateParameters($source, array(
            "path" => $csvFile,
            "name" => "ActiviteCSV",
            "date" => 0,
            "title" => 1,
            "content" => 2,
            "attributes" => array("Attribut1" => 3)
        ));

        $this->assertSame($source->getImporter(), "Csv");
        $this->assertSame($source->getParameter("path"), $csvFile);
        $this->assertSame($source->getParameter("name"), "ActiviteCSV");
        $this->assertSame($source->getTitle(), $csvFile);
        $this->assertSame($source->getUpdateParam(), null);

        $importer->run($source, new \Symfony\Component\Console\Output\NullOutput(), true, false);

        $em->getUnitOfWork()->computeChangeSets();
        $entities = $em->getUnitOfWork()->getScheduledEntityInsertions();

        $activities = array();
        foreach($entities as $activity) {
            if(!$activity instanceof \AppBundle\Entity\Activity) {
                continue;
            }
            $activities[$activity->getExecutedAt()->format('YmdHis').uniqid()] = $activity;
        }

        $this->assertCount($nbLines, $activities);

        $activity = current($activities);

        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20200202020202");
        $this->assertSame($activity->getTitle(), "Titre de l'activité");
        $this->assertSame($activity->getContent(), "Contenu de l'activité");
        $this->assertSame($activity->getSlug(), "6200228a8ebcbc2ef3e05b1cbf6b526a");

        $this->assertCount(2, $activity->getAttributes());

        $this->assertSame($activity->getAttributes()[0]->getName(), "Name");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "ActiviteCSV");

        $this->assertSame($activity->getAttributes()[1]->getName(), "Attribut1");
        $this->assertSame($activity->getAttributes()[1]->getValue(), "Valeur1");
    }
}

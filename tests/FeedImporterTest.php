<?php

namespace Tests;

use AppBundle\Entity\Source;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FeedImporterTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testRun()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $importer = $this->container->get('app.importer.feed');
        $url = "file://".dirname(__FILE__)."/data/framadate.atom";

        $nb = 13;
        $source = new Source();
        $source->setImporter($importer->getName());
        $importer->updateParameters($source, array(
                "uri" => $url,
                "name" => "Framadate"
        ));

        $this->assertSame($source->getImporter(), "Feed");
        $this->assertSame($source->getParameter("uri"), $url);
        $this->assertSame($source->getParameter("name"), "Framadate");
        $this->assertSame($source->getTitle(), $url);
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

        ksort($activities);

        $this->assertCount($nb, $activities);

        next($activities);
        $activity = current($activities);

        $this->assertSame($activity->getSlug(), "26ef23684ec6bf044ff04e8f5a1d1561");
        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20170412200219");
        $this->assertSame($activity->getTitle(), "JACQUES opened issue #230: Mise à jour Framadate at Framasoft / framadate");
        $this->assertSame($activity->getContent(), "");

        $this->assertCount(2, $activity->getAttributes());
        $this->assertSame($activity->getAttributes()[0]->getName(), "Name");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "Framadate");
        $this->assertSame($activity->getAttributes()[1]->getName(), "Author");
        $this->assertSame($activity->getAttributes()[1]->getValue(), "JACQUES");
    }
}

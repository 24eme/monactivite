<?php

namespace Tests;

use AppBundle\Entity\Source;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ICalendarTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testICalendar()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $importer = $this->container->get('app.importer.icalendar');
        $icsFile = dirname(__FILE__)."/data/calendrier.ics";
        $nbEvents = 6;

        $source = new Source();
        $source->setImporter($importer->getName());
        $importer->updateParameters($source, array(
            "uri" => $icsFile,
            "name" => "Calendrier"
        ));

        $this->assertSame($source->getImporter(), "ICalendar");
        $this->assertSame($source->getParameter("uri"), $icsFile);
        $this->assertSame($source->getParameter("name"), "Calendrier");
        $this->assertSame($source->getTitle(), $icsFile);
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

        $this->assertCount($nbEvents, $activities);

        $activity = current($activities);

        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20160314080000");
        $this->assertSame($activity->getTitle(), "Événement simple sur une journée");
        $this->assertSame($activity->getContent(), "Événement de test\nSur une journée");
        $this->assertSame($activity->getSlug(), "9a5f83745e1d55cf96ea9edac567cf8b");

        $this->assertCount(1, $activity->getAttributes());

        $this->assertSame($activity->getAttributes()[0]->getName(), "Type");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "Event");

        next($activities);
        $activity = current($activities);

        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20160315140000");
        $this->assertSame($activity->getTitle(), "Évenement simple à une heure précise");
        $this->assertSame($activity->getContent(), "Événement de test\nÀ une une heure précise");
        $this->assertSame($activity->getSlug(), "ccfe0594ebb51833d4cff2610fe19c5a");

        $this->assertCount(1, $activity->getAttributes());

        $this->assertSame($activity->getAttributes()[0]->getName(), "Type");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "Event");

        $events = array("f605f937ec6be1487941b5bb24d5cd1b" => "20160321080000",
                        "69f1e86be70ea24ca42e7f957c795be6" => "20160322080000",
                        "c373432ffd4fe521c556cb7191b378bf" => "20160323080000",
                        "c90d3407041f1b2f31fee9454aa38a1c" => "20160324080000");

        foreach($events as $slug => $date) {
            next($activities);
            $activity = current($activities);

            $this->assertSame($activity->getExecutedAt()->format('YmdHis'), $date);
            $this->assertSame($activity->getTitle(), "Événement sur plusieurs jours");
            $this->assertSame($activity->getContent(), "Événement de test\nSur 4 jours");
            $this->assertSame($activity->getSlug(), $slug);

            $this->assertCount(1, $activity->getAttributes());

            $this->assertSame($activity->getAttributes()[0]->getName(), "Type");
            $this->assertSame($activity->getAttributes()[0]->getValue(), "Event");
        }
    }
}

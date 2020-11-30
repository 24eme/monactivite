<?php

namespace Tests;

use AppBundle\Entity\Source;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MailImporterTest extends KernelTestCase
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
        $importer = $this->container->get('app.importer.mail');
        $mailFile = dirname(__FILE__)."/data/mails";
        $nbMails = 2;

        $source = new Source();
        $source->setImporter($importer->getName());
        $importer->updateParameters($source, array(
            "path" => $mailFile,
        ));

        $this->assertSame($source->getImporter(), "Mail");
        $this->assertSame($source->getParameter("path"), $mailFile);
        $this->assertSame($source->getUpdateParam(), null);

        $importer->run($source, new \Symfony\Component\Console\Output\NullOutput(), true, false);

        $em->getUnitOfWork()->computeChangeSets();
        $entities = $em->getUnitOfWork()->getScheduledEntityInsertions();

        $activities = array();
        foreach($entities as $activity) {
            if(!$activity instanceof \AppBundle\Entity\Activity) {
                continue;
            }
            $activities[$activity->getExecutedAt()->format('YmdHis')] = $activity;
        }

        ksort($activities);

        $this->assertCount($nbMails, $activities);

        $activity = current($activities);

        $this->assertSame($activity->getSlug(), "f98199cbd7cfe2bf6b991ac4e86fbd15");
        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20170222000521");
        $this->assertSame($activity->getTitle(), "Test mail text/plain");
        $this->assertSame($activity->getContent(), "Bonjour,\n\nCeci est un mail en text/plain tout simple, qui contient même des accents.\n\nLe testeur");

        $this->assertCount(5, $activity->getAttributes());

        $this->assertSame($activity->getAttributes()[0]->getName(), "Type");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "Mail");
        $this->assertSame($activity->getAttributes()[1]->getName(), "Sender");
        $this->assertSame($activity->getAttributes()[1]->getValue(), "sender@example.org");
        $this->assertSame($activity->getAttributes()[2]->getName(), "Recipient");
        $this->assertSame($activity->getAttributes()[2]->getValue(), "recipient@example.org");
        $this->assertSame($activity->getAttributes()[3]->getName(), "To");
        $this->assertSame($activity->getAttributes()[3]->getValue(), "recipient@example.org");
        $this->assertSame($activity->getAttributes()[4]->getName(), "Cc");
        $this->assertSame($activity->getAttributes()[4]->getValue(), "recipient_copy@example.org");

        next($activities);
        $activity = current($activities);

        $this->assertSame($activity->getSlug(), "6027e5b6e33497d91d1c7a86fa3a1a92");
        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20170222004704");
        $this->assertSame($activity->getTitle(), "Test mail text/html");
        $this->assertSame($activity->getContent(), "Bonjour,\n\nCeci est un mail *html*.\n\nAvec une liste\n\n   - Point n°1\n   - Point n°2\n   - Point n°3\n\nLe testeur");

        $this->assertCount(5, $activity->getAttributes());

        $this->assertSame($activity->getAttributes()[0]->getName(), "Type");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "Mail");
        $this->assertSame($activity->getAttributes()[1]->getName(), "Sender");
        $this->assertSame($activity->getAttributes()[1]->getValue(), "sender@example.org");
        $this->assertSame($activity->getAttributes()[2]->getName(), "Recipient");
        $this->assertSame($activity->getAttributes()[2]->getValue(), "recipient_primaire@example.org");
        $this->assertSame($activity->getAttributes()[3]->getName(), "To");
        $this->assertSame($activity->getAttributes()[3]->getValue(), "recipient_primaire@example.org, recipient_secondaire@example.org");
        $this->assertSame($activity->getAttributes()[4]->getName(), "Cc");
        $this->assertSame($activity->getAttributes()[4]->getValue(), "recipient_copy@example.org");
        $this->assertSame($source->getUpdateParam()['line'], (int) shell_exec("cat ".$mailFile." | wc -l"));

    }

    public function testWindows1256()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $importer = $this->container->get('app.importer.mail');
        $mailFile = dirname(__FILE__)."/data/windows1256.eml";

        $source = new Source();
        $source->setImporter($importer->getName());
        $importer->updateParameters($source, array(
            "path" => $mailFile,
        ));

        $importer->run($source, new \Symfony\Component\Console\Output\NullOutput(), true, false);

        $em->getUnitOfWork()->computeChangeSets();
        $entities = $em->getUnitOfWork()->getScheduledEntityInsertions();

        $activities = array();
        foreach($entities as $activity) {
            if(!$activity instanceof \AppBundle\Entity\Activity) {
                continue;
            }
            $activities[] = $activity;
        }

        $this->assertCount(1, $activities);
        $this->assertRegExp("/à/", $activities[0]->getContent());
    }
}

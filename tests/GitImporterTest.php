<?php

namespace Tests;

use AppBundle\Entity\Source;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GitImporterTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testGitImporter()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $importer = $this->container->get('app.importer.git');
        $gitDir = preg_replace("|/tests$|", "", dirname(__FILE__));
        $nbCommits = shell_exec("cd ".$gitDir."; git log | grep -E \"^commit \" | wc -l")*1;

        $source = new Source();
        $source->setImporter($importer->getName());
        $importer->updateParameters($source, array(
                "name" => "monactivite",
        ));

        $this->assertSame($source->getParameter("name"), "monactivite");

        $importer->updateParameters($source, array(
                "path" => preg_replace("|/app$|", "", self::$kernel->getRootDir()),
                "name" => null,
        ));

        $this->assertSame($source->getImporter(), "Git");
        $this->assertSame($source->getParameter("path"), $gitDir);
        $this->assertSame($source->getParameter("name"), "monactivite.git");
        $this->assertSame($source->getTitle(), $gitDir);
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

        $this->assertCount($nbCommits, $activities);

        next($activities);
        $activity = current($activities);

        $this->assertSame($activity->getSlug(), "f255a5c4f31bcee397080d7329c42de5");
        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20150605172343");
        $this->assertSame($activity->getTitle(), "Initial commit");
        $this->assertSame($activity->getContent(), "LICENSE | 22 ++++++++++++++++++++++\n 1 file changed, 22 insertions(+)");

        $this->assertCount(3, $activity->getAttributes());
        $this->assertSame($activity->getAttributes()[0]->getName(), "Type");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "Commit");
        $this->assertSame($activity->getAttributes()[1]->getName(), "Repository");
        $this->assertSame($activity->getAttributes()[1]->getValue(), "monactivite.git");
        $this->assertSame($activity->getAttributes()[2]->getName(), "Author");
        $this->assertSame($activity->getAttributes()[2]->getValue(), "vince.laurent@gmail.com");

        $this->assertSame($source->getUpdateParam()['date'], date('Y-m-d'));
    }
}

<?php

namespace Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Tag;
use Symfony\Component\Yaml\Yaml;

class ActivityTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function test()
    {
        $att1 = new ActivityAttribute();
        $att1->setName("Author");
        $att1->setValue("Moi");

        $att2 = new ActivityAttribute();
        $att2->setName("Type");
        $att2->setValue("Commit");

        $tag1 = new Tag();
        $tag1->setName("Commit");

        $tag2 = new Tag();
        $tag2->setName("Project");

        $activity = new Activity();
        $activity->setTitle("Test titre");
        $activity->setContent("Test contenu\nTest contenu");
        $activity->setExecutedAt(new \DateTime("2018-01-24 08:09:10"));
        $activity->addTag($tag1);
        $activity->addTag($tag2);
        $activity->addAttribute($att1);
        $activity->addAttribute($att2);


        $this->assertSame($activity->toCSV(), '2018-01-24 08:09:10;Commit,Project;"Test titre";Author:Moi,Type:Commit;"Test contenu\nTest contenu"');
    }

}

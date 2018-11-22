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

        $repo = $this->container->get('doctrine.orm.entity_manager')->getRepository('AppBundle:Activity');

        $this->assertSame($repo->normalizeQuery("Vincent Laurent"), "Vincent AND Laurent");
        $this->assertSame($repo->normalizeQuery("\"Vincent Laurent\""), "Vincent Laurent");
        $this->assertSame($repo->normalizeQuery("Sender:\"V. LAURENT\""), "Sender:V. LAURENT");
        $this->assertSame($repo->normalizeQuery("Sender:V. LAURENT"), "Sender:V. AND LAURENT");
        $this->assertSame($repo->normalizeQuery("Vincent OR LAURENT"), "Vincent OR LAURENT");

        $this->assertSame($repo->normalizeQuery("Vincent Laurent OR winy Receiver:vlaurent@24eme.fr \"Laurent Vincent\" AND vince Sender:\"V. LAURENT\" Type:Commit"), "Vincent AND Laurent OR winy AND Receiver:vlaurent@24eme.fr AND Laurent Vincent AND vince AND Sender:V. LAURENT AND Type:Commit");


        $this->assertSame($repo->queryToArray("Vincent"), array(array('*', 'Vincent')));
        $this->assertSame($repo->queryToArray("Vincent LAURENT"), array(array('*', 'Vincent'), array('*', 'LAURENT')));
        $this->assertSame($repo->queryToArray("\"Vincent LAURENT\""), array(array('*', 'Vincent LAURENT')));
        $this->assertSame($repo->queryToArray("Sender:Vincent LAURENT"), array(array('Sender', 'Vincent'), array('*', 'LAURENT')));
        $this->assertSame($repo->queryToArray("Sender:\"Vincent LAURENT\""), array(array('Sender', 'Vincent LAURENT')));
        $this->assertSame($repo->queryToArray("Sender:\"Vincent LAURENT\""), array(array('Sender', 'Vincent LAURENT')));
        $this->assertSame($repo->queryToArray("Vincent OR LAURENT"), array(array('*', 'Vincent'), array('*', 'LAURENT')));
        $this->assertSame($repo->queryToHierarchy("Vincent LAURENT"), "and");
        $this->assertSame($repo->queryToHierarchy("Vincent AND LAURENT"), "and");
        $this->assertSame($repo->queryToHierarchy("Vincent OR LAURENT"), "or");
    }

}

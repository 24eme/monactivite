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

    public function testExportCsv()
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

    public function testQuery()
    {
        $repo = $this->container->get('doctrine.orm.entity_manager')->getRepository('AppBundle:Activity');

        $this->assertSame($repo->normalizeQuery("Vincent Laurent"), "Vincent AND Laurent");
        $this->assertSame($repo->normalizeQuery("\"Vincent Laurent\""), "Vincent Laurent");
        $this->assertSame($repo->normalizeQuery("Sender:\"V. LAURENT\""), "Sender:V. LAURENT");
        $this->assertSame($repo->normalizeQuery("Sender:V. LAURENT"), "Sender:V. AND LAURENT");
        $this->assertSame($repo->normalizeQuery("Vincent OR LAURENT"), "Vincent OR LAURENT");
        $this->assertSame($repo->normalizeQuery("Vincent NOT LAURENT"), "Vincent AND NOT LAURENT");
        $this->assertSame($repo->normalizeQuery("NOT Vincent OR NOT LAURENT"), "NOT Vincent OR NOT LAURENT");
        $this->assertSame($repo->normalizeQuery("NOT Sender:\"V. LAURENT\" NOT Receiver:vlaurent@24eme.fr"), "NOT Sender:V. LAURENT AND NOT Receiver:vlaurent@24eme.fr");
        $this->assertSame($repo->normalizeQuery("Vincent Laurent OR winy Receiver:vlaurent@24eme.fr \"Laurent Vincent\" AND vince Sender:\"V. LAURENT\" Type:Commit"), "Vincent AND Laurent OR winy AND Receiver:vlaurent@24eme.fr AND Laurent Vincent AND vince AND Sender:V. LAURENT AND Type:Commit");

        $this->assertSame($repo->queryToArray("Vincent"), array(array('*', 'Vincent')));
        $this->assertSame($repo->queryToArray("Vincent LAURENT"), array(array('*', 'Vincent'), array('*', 'LAURENT')));
        $this->assertSame($repo->queryToArray("\"Vincent LAURENT\""), array(array('*', 'Vincent LAURENT')));
        $this->assertSame($repo->queryToArray("Sender:Vincent LAURENT"), array(array('Sender', 'Vincent'), array('*', 'LAURENT')));
        $this->assertSame($repo->queryToArray("Sender:\"Vincent LAURENT\""), array(array('Sender', 'Vincent LAURENT')));
        $this->assertSame($repo->queryToArray("Sender:\"Vincent LAURENT\""), array(array('Sender', 'Vincent LAURENT')));
        $this->assertSame($repo->queryToArray("Vincent OR LAURENT"), array(array('*', 'Vincent'), array('*', 'LAURENT')));
        $this->assertSame($repo->queryToArray("(Vincent OR LAURENT) AND Paris"), array(array('*', 'Vincent'), array('*', 'LAURENT'), array('*', 'Paris')));
        $this->assertSame($repo->queryToArray("NOT Vincent"), array(array('*', 'Vincent', "not")));
        $this->assertSame($repo->queryToArray("NOT Vincent OR NOT Test"), array(array('*', 'Vincent', 'not'), array('*', 'Test', 'not')));

        $this->assertSame($repo->queryToHierarchy(""), array());
        $this->assertSame($repo->queryToHierarchy("Vincent"), array());
        $this->assertSame($repo->queryToHierarchy("Vincent LAURENT Paris"), array("and", "and"));
        $this->assertSame($repo->queryToHierarchy("Vincent AND LAURENT"), array("and"));
        $this->assertSame($repo->queryToHierarchy("Vincent OR LAURENT"), array("or"));
        $this->assertSame($repo->queryToHierarchy("Vincent LAURENT OR Paris AND 10"), array("and", "or", "and"));
        $this->assertSame($repo->queryToHierarchy("(Vincent LAURENT)"), array("(and)"));
        $this->assertSame($repo->queryToHierarchy("(Vincent OR LAURENT) AND Paris"), array("(or)", "and"));
        $this->assertSame($repo->queryToHierarchy("((Vincent OR LAURENT) AND (Paris AND Commmit))"), array("((or)", "and", "(and))"));

        $queryDateQDL = "\(aq[0-9]*\.executedAt >= :date_to AND aq[0-9]*\.executedAt <= :date_from\)";
        $queryPartGenericDQL="\(aq[0-9]*\.title LIKE :aq[0-9]+value OR aq[0-9]*\.content LIKE :aq[0-9]+value OR [a-z0-9]+\.value LIKE :aq[0-9]+value OR [a-z0-9]+\.name LIKE :aq[0-9]+value\)";
        $queryPartContentDQL="\(aq[0-9]*\.(title|content) LIKE :aq[0-9]+value\)";
        $queryPartAttributeDQL="\(aqa[0-9]+.value LIKE :aq[0-9]+value AND aqa[0-9]+.name LIKE :aq[0-9]+name\)";
        $queryPartTagDQL="\(aq[0-9]*t[0-9]+.name LIKE :aq[0-9]+value\)";
        $queryNotPartGenericDQL =  $queryDateQDL.' AND \(\(aq\.id NOT IN\(SELECT aq[0-9]* FROM AppBundle:Activity aq[0-9]* LEFT JOIN aq[0-9]*\.attributes aq[0-9]*a[0-9a-z]* LEFT JOIN aq[0-9]*\.tags aq[0-9]*t[0-9a-z]* WHERE '.$queryDateQDL.' AND \('.$queryPartGenericDQL.'\)\)\)\)';
        $queryNotPartTagDQL = '\(aq\.id NOT IN\(SELECT aq[0-9]* FROM AppBundle:Activity aq[0-9]* LEFT JOIN aq[0-9]*\.tags aq[0-9]*t[0-9]* WHERE '.$queryDateQDL.' AND \('.$queryPartTagDQL.'\)\)\)';

        $queryResult = $repo->searchQueryToQueryDoctrine("Vincent LAURENT OR Paris", "2018-01-01", "2019-01-01");
        $this->assertRegExp('/'.$queryDateQDL.' AND \('.$queryPartGenericDQL.' AND '.$queryPartGenericDQL.' OR '.$queryPartGenericDQL.'\)/', $queryResult->getDQLPart("where"));

        $queryResult = $repo->searchQueryToQueryDoctrine("tag:Commit", "2018-01-01", "2019-01-01");
        $this->assertRegExp('/'.$queryDateQDL.' AND \('.$queryPartTagDQL.'\)/', $queryResult->getDQLPart("where"));

        $queryResult = $repo->searchQueryToQueryDoctrine("Sender:test@mail.org OR Author:\"Vincent LAURENT\"", "2018-01-01", "2019-01-01");
        $this->assertRegExp('/'.$queryDateQDL.' AND \('.$queryPartAttributeDQL.' OR '.$queryPartAttributeDQL.'\)/', $queryResult->getDQLPart("where"));

        $queryResult = $repo->searchQueryToQueryDoctrine("tag:Commit OR NOT tag:Mail", "2018-01-01", "2019-01-01");
        $this->assertRegExp('/'.$queryDateQDL.' AND \('.$queryPartTagDQL.' OR '.$queryNotPartTagDQL.'\)/', $queryResult->getDQLPart("where"));

        $queryResult = $repo->searchQueryToQueryDoctrine("NOT vince", "2018-01-01", "2019-01-01");
        $this->assertRegExp('/'.$queryNotPartGenericDQL.'/', $queryResult->getDQLPart("where"));

        $queryResult = $repo->searchQueryToQueryDoctrine("title:Bonjour content:Cordialement", "2018-01-01", "2019-01-01");
        $this->assertRegExp('/'.$queryDateQDL.' AND \('.$queryPartContentDQL.' AND '.$queryPartContentDQL.'\)/', $queryResult->getDQLPart("where"));

        $queryResult = $repo->searchQueryToQueryDoctrine("(title:Bonjour OR content:Cordialement) AND (Type:Commit OR Type:Mail)", "2018-01-01", "2019-01-01");
        $this->assertRegExp('/'.$queryDateQDL.' AND \(\('.$queryPartContentDQL.' OR '.$queryPartContentDQL.'\) AND \('.$queryPartAttributeDQL.' OR '.$queryPartAttributeDQL.'\)\)/', $queryResult->getDQLPart("where"));
    }

}

<?php

namespace Tests;

use AppBundle\Entity\Source;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\NullOutput;

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
        $nbCommits = (int) shell_exec("cd ".$gitDir."; git log --branches | grep -E \"^commit \" | wc -l");

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

        try {
            $importer->check($source);
        } catch (\Exception $e) {
            $this->fail();
        }

        $this->assertFalse($importer->isRemote($source));
        $this->assertSame($source->getImporter(), "Git");
        $this->assertSame($source->getParameter("path"), $gitDir);
        $this->assertSame($source->getParameter("name"), "monactivite.git");
        $this->assertSame($source->getTitle(), $gitDir);
        $this->assertSame($source->getUpdateParam(), null);

        $importer->run($source, new NullOutput(), true, false);

        $activities = $this->getActivitiesInsertions($em);

        $this->assertCount($nbCommits, $activities);

        next($activities);
        $activity = current($activities);

        $this->assertSame($activity->getSlug(), "f255a5c4f31bcee397080d7329c42de5");
        $this->assertSame($activity->getExecutedAt()->format('YmdHis'), "20150605172343");
        $this->assertSame($activity->getTitle(), "Initial commit");
        $this->assertSame($activity->getContent(), "LICENSE | 22 ++++++++++++++++++++++\n 1 file changed, 22 insertions(+)");

        $this->assertCount(4, $activity->getAttributes());
        $this->assertSame($activity->getAttributes()[0]->getName(), "Type");
        $this->assertSame($activity->getAttributes()[0]->getValue(), "Commit");
        $this->assertSame($activity->getAttributes()[1]->getName(), "Repository");
        $this->assertSame($activity->getAttributes()[1]->getValue(), "monactivite.git");
        $this->assertSame($activity->getAttributes()[2]->getName(), "Author");
        $this->assertSame($activity->getAttributes()[2]->getValue(), "vince.laurent@gmail.com");
        $this->assertSame($activity->getAttributes()[3]->getName(), "Branch");
        $this->assertSame($activity->getAttributes()[3]->getValue(), "master");

        $activityMerge = null;
        foreach($activities as $activity) {
            if(!preg_match("/Merge branch /", $activity->getTitle())) {
                continue;
            }

            $activityMerge = $activity;
            break;
        }

        $this->assertNotEmpty($activityMerge->getContent());

        $this->assertSame($source->getUpdateParam()['date'], date('Y-m-d'));

        $em->getUnitOfWork()->clear();
        $nbCommits = (int) shell_exec("cd ".$gitDir."; git log master | grep -E \"^commit \" | wc -l");

        $source = new Source();
        $source->setImporter($importer->getName());
        $importer->updateParameters($source, array(
                "path" => preg_replace("|/app$|", "", self::$kernel->getRootDir()),
                "name" => null,
                "branch" => "master"
        ));

        $this->assertSame($source->getParameter("branch"), "master");

        $importer->run($source, new NullOutput(), true, false);

        $activities = $this->getActivitiesInsertions($em);

        $this->assertCount($nbCommits, $activities);

        $activity = current($activities);
        $this->assertCount(4, $activity->getAttributes());
        $this->assertSame($activity->getAttributes()[3]->getName(), "Branch");
        $this->assertSame($activity->getAttributes()[3]->getValue(), "master");
    }

    public function testGitImporterWithClone()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $importer = $this->container->get('app.importer.git');
        $slugger = $this->container->get('app.slugger');

        $testTmpDir = dirname(__FILE__)."/tmp";
        $repoDir = "git_repo.git";
        $gitUri = "file://".$testTmpDir."/".$repoDir;

        shell_exec("mkdir ".$testTmpDir." 2> /dev/null; cd ".$testTmpDir."; rm -rf git_repo*; mkdir ".$repoDir." 2> /dev/null; cd ".$repoDir."; git init --bare; cd -; git clone ".$gitUri." git_repo_clone > /dev/null 2>&1; cd git_repo_clone; echo \"coucou\" > test; git add test; git commit -m \"test\" > /dev/null 2>&1; git push > /dev/null 2>&1");

        $source = new Source();
        $importer->updateParameters($source, array(
                "path" => $gitUri,
                "name" => null,
        ));

        try {
            $importer->check($source);
        } catch (\Exception $e) {
            $this->fail();
        }

        $this->assertTrue($importer->isRemote($source));
        $this->assertSame($source->getParameter("name"), $repoDir);

        $importer->run($source, new NullOutput(), true, false);

        $this->assertFileExists($importer->getVarDir()."/".$slugger->slugify($gitUri)."/HEAD");

        $activities = $this->getActivitiesInsertions($em);

        $this->assertCount(1, $activities);

        shell_exec("cd ".$testTmpDir."/git_repo_clone; echo \"coucou2\" > test2; git add test2; git commit -m \"test2\" > /dev/null 2>&1; git push > /dev/null 2>&1");

        $importer->run($source, new NullOutput(), true, false);

        $activities = $this->getActivitiesInsertions($em);

        $this->assertCount(1, $activities);
    }

    public function getActivitiesInsertions($em) {
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

        return $activities;
    }
}

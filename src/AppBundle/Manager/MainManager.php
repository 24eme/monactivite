<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Source;
use AppBundle\Entity\Filter;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\DataFixtures\Loader as DataFixturesLoader;
use AppBundle\DataFixtures\ORM\LoadTagData;
use AppBundle\DataFixtures\ORM\LoadFilterData;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

class MainManager
{
    protected $em;
    protected $am;
    protected $im;
    protected $fm;
    protected $sm;

    public function __construct($em, $am, $im, $fm, $sm) {
        $this->em = $em;
        $this->am = $am;
        $this->im = $im;
        $this->fm = $fm;
        $this->sm = $sm;
    }

    public function executeSource(Source $source, OutputInterface $output, $dryRun = false) {

        return $this->sm->executeOne($source, $output, $dryRun);
    }

    public function executeAllSources(OutputInterface $output, $dryRun = false) {

        return $this->sm->executeAll($output, $dryRun);
    }

    public function executeFilter(Filter $filter, OutputInterface $output, $dryRun = false) {
        
        return $this->fm->executeOne($filter, $output, $dryRun);
    }

    public function executeAllFilters(OutputInterface $output, $dryRun = false) {
        
        return $this->fm->executeAll($output, $dryRun);
    }

    public function executeOne(Source $source, OutputInterface $output, $dryRun = false) {
        $this->executeSource($source, $output, $dryRun);
        $this->executeAllFilters($output, $dryRun);
    }

    public function executeAll(OutputInterface $output, $dryRun = false) {
        $this->executeAllSources($output, $dryRun);
        $this->executeAllFilters($output, $dryRun);
    }

    /*public function loadFixtures() {
        $loader = new DataFixturesLoader();
        $loader->addFixture(new LoadTagData());
        $loader->addFixture(new LoadFilterData());

        $executor = new ORMExecutor($this->em);
        $executor->execute($loader->getFixtures(), true);
    }*/

}

<?php 

namespace AppBundle\Manager;

use AppBundle\Entity\Source;
use Symfony\Component\Console\Output\OutputInterface;
    
class SourceManager
{
    protected $em;
    protected $repository;
    protected $slugger;
    protected $im;

    public function __construct($em, $slugger, $im) {
        $this->em = $em;
        $this->repository = $em->getRepository('AppBundle:Source');
        $this->slugger = $slugger;
        $this->im = $im;
    }

    public function executeOne(Source $source, OutputInterface $output, $dryRun = false) {

        return $this->im->execute($source, $output, $dryRun);
    }

    public function executeAll(OutputInterface $output, $dryRun = false) {
        $sources = $this->repository->findAll();
        foreach($sources as $source) {
            $this->executeOne($source, $output, $dryRun);
        }
    }

}
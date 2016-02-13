<?php

namespace AppBundle\Importer;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Source;

abstract class Importer
{
    protected $am;
    protected $em;

    public function __construct($am, $em)
    {
        $this->am = $am;
        $this->em = $em;
    }

    public abstract function run(Source $source, OutputInterface $output, $dryrun = false);

    public function check(Source $source) {
        if(!file_exists($this->getVarDir())) {
            mkdir($this->getVarDir());
        }

        if(!$this->getVarDir()) {
            throw new \Exception(sprintf("var folder doesn't exist : %s", $this->getVarDir()));
        }

        if(!is_writable($this->getVarDir())) {
            throw new \Exception(sprintf("var folder isn't writable : %s", $this->getVarDir()));
        }
    }

    public abstract function getRootDir();

    public abstract function getName();

    public function getVarDir() {

        return $this->getRootDir()."/var";
    }
} 
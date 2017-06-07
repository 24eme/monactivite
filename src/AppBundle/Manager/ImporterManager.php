<?php

namespace AppBundle\Manager;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Source;

class ImporterManager
{
    protected $importers = null;

    public function __construct($importers) {
        $this->importers = array();
        foreach($importers as $importer) {
            $this->importers[$importer->getName()] = $importer;
        }
    }

    public function execute(Source $source, OutputInterface $output, $dryRun = false) {
        $importer = $this->get($source->getImporter());

        $importer->check($source);
        $importer->run($source, $output, $dryRun);
    }

    public function getImporters() {

        return $this->importers;
    }

    public function get($name) {
        if(!isset($this->importers[$name])) {

            throw new \Exception(sprintf("Importer %s doesn't exist", $name));
        }

        return $this->importers[$name];
    }

    public function search(Source $source) {
        foreach($this->importers as $importer) {
            try{
                $importer->check($source);

                return $importer;
            } catch(\Exception $e) {
                continue;
            }
        }

        return null;
    }
}

<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Source;

class ImporterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('monactivite:importer')
            ->setDescription('Run importer')
            ->addArgument('importer', InputArgument::REQUIRED, 'Name of the importer')
            ->addArgument('source', InputArgument::REQUIRED, 'Source')
            ->addArgument('name', InputArgument::REQUIRED, 'Source name')
            ->addOption('dry-run', 't', InputOption::VALUE_NONE, 'Try import but not store in database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $im = $this->getContainer()->get('app.manager.importer');

        $source =  new Source();
        $source->setImporter($input->getArgument('importer'));
        $source->setSource($input->getArgument('source'));
        $source->setName($input->getArgument('name'));

        $im->execute($source,
                     $output,
                     true);
    }
}
?>

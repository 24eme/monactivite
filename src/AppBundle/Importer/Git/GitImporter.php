<?php

namespace AppBundle\Importer\Git;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;

class GitImporter extends Importer
{
    public function run($source, $sourceName = null, OutputInterface $output, $dryrun = false) {
        $output->writeln(sprintf("<comment>Started import git commit in %s</comment>", $source));

        $storeFile = $this->storeCsv($source);

        $nb = 0;

        foreach(file($storeFile) as $line) {
            $datas = str_getcsv($line, ";", '"');

            $activity = new Activity();
            $activity->setExecutedAt(isset($datas[3]) ? new \DateTime(trim($datas[3])) : null);
            $activity->setTitle(isset($datas[4]) ? trim($datas[4]) : null);
            $activity->setContent(isset($datas[5]) ? trim($datas[5]) : null);

            $type = new ActivityAttribute();
            $type->setName("Type");
            $type->setValue("Commit");

            $repository = new ActivityAttribute();
            $repository->setName("Repository");
            $repository->setValue($sourceName);

            $author = new ActivityAttribute();
            $author->setName("Author");
            $author->setValue(isset($datas[2]) ? trim($datas[2]) : null);

            $activity->addAttribute($type);
            $activity->addAttribute($repository);
            $activity->addAttribute($author);

            try {
                $this->am->addFromEntity($activity);

                if(!$dryrun) {
                    $this->em->persist($type);
                    $this->em->persist($repository);
                    $this->em->persist($author);
                    $this->em->persist($activity);
                    $this->em->flush($activity);
                }

                $nb++;

                if($output->isVerbose()) {
                    $output->writeln(sprintf("<info>Imported</info> %s", $activity->getTitle()));
                }
            } catch (\Exception $e) {
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error> %s", $e->getMessage(), $activity->getTitle()));
                }
            }
        }

        unlink($storeFile);

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check($source) {
        parent::check($source);

        if(!file_exists($source)) {
            throw new \Exception(sprintf("Folder %s doesn't exist", $source));
        }

        if(!file_exists($source."/.git")) {
            throw new \Exception(sprintf("This folder isn't a git repository", $source));
        }
    }

    protected function storeCsv($file) {
        $storeFile = sprintf("%s/var/commits_%s_%s.csv", dirname(__FILE__), date("YmdHis"), uniqid());
        
        shell_exec(sprintf("%s/bin/git2csv.sh %s > %s", dirname(__FILE__), $file, $storeFile));
    
        return $storeFile;
    }

} 
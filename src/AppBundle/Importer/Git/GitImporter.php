<?php

namespace AppBundle\Importer\Git;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;

class GitImporter extends Importer
{
    public function run($source, $sourceName = null, OutputInterface $output, $dryrun = false) {
        $output->writeln(sprintf("<comment>Started import git commit in %s</comment>", $source));

        $storeFile = $this->storeCsv($source);

        $nb = 0;

        foreach(file($storeFile) as $line) {
            $datas = str_getcsv($line, ";", '"');

            $date = isset($datas[3]) ? $datas[3] : null;
            $title = isset($datas[4]) ? $datas[4] : null;
            $content = isset($datas[5]) ? $datas[5] : null;
            $author = isset($datas[2]) ? $datas[2] : null;

            try {
                $activity = $this->am->fromArray(array(
                    'title' => $title,
                    'executed_at' => $date,
                    'author' => $author,
                    'content' => $content,
                    'destination' => null,
                    'source' => sprintf("%s <%s>", $sourceName, $source),
                ));

                if(!$dryrun) {
                    $this->em->persist($activity);
                    $this->em->flush($activity);
                }

                $nb++;

                if($output->isVerbose()) {
                    $output->writeln(sprintf("<info>Imported</info>;%s;%s", $date, $title));
                }
            } catch (\Exception $e) {
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error>;%s;%s", $e->getMessage(), $date, $title));
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
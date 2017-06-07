<?php

namespace AppBundle\Importer\Git;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;

class GitImporter extends Importer
{
    public function getName() {

        return 'Git';
    }

    public function getDescription() {

        return "Récupère les commits d'un dêpot git à partir d'un dossier en local";
    }

    public function getParameters() {

        return array(
            'path' => array("required" => true, "label" => "Chemin", "help" => "Chemin vers le dossier du projet git"),
            'name' => array("required" => false, "label" => "Nom", "help" => "Nom du dépot git (optionelle, calculer automatiquement)"),
            'author' => array("required" => false, "label" => "Auteur", "help" => "Filtrer l'auteur des commits, grâce à une expression régulière (optionelle)"),
        );
    }

    public function updateParameters(Source $source, $parameters) {
        parent::updateParameters($source, $parameters);
        if(!$source->getParameter('name')) {
            $source->setParameter('name', basename($source->getParameter('path')).".git");
        }
    }

    public function updateTitle(Source $source) {
        $source->setTitle($source->getParameter('path'));
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import git commit in %s</comment>", $source->getTitle()));

        $storeFile = $this->storeCsv($source);

        $repositoryName = $source->getParameter('name');

        $nb = 0;

        foreach(file($storeFile) as $line) {
            $datas = str_getcsv($line, ";", '"');

            $authorEmail = isset($datas[2]) ? trim(preg_replace("/^.+<(.+)>$/", '\1', $datas[2])) : null;

            if($source->getParameter('author') && !preg_match("/".$source->getParameter('author')."/", $authorEmail)) {
                continue;
            }

            try {
                $activity = new Activity();
                $activity->setExecutedAt(isset($datas[3]) ? new \DateTime(trim($datas[3])) : null);
                $activity->setTitle(isset($datas[4]) ? trim($datas[4]) : null);
                $activity->setContent(isset($datas[5]) ? str_replace('\n', "\n", trim($datas[5])) : null);

                $type = new ActivityAttribute();
                $type->setName("Type");
                $type->setValue("Commit");

                $repository = new ActivityAttribute();
                $repository->setName("Repository");
                $repository->setValue($repositoryName);

                $author = new ActivityAttribute();
                $author->setName("Author");
                $author->setValue($authorEmail);

                $activity->addAttribute($type);
                $activity->addAttribute($repository);
                $activity->addAttribute($author);

                $this->am->addFromEntity($activity, $checkExist);

                $this->em->persist($type);
                $this->em->persist($repository);
                $this->em->persist($author);
                $this->em->persist($activity);

                if(!$dryrun) {
                    $this->em->flush($activity);
                }

                $nb++;

                if($output->isVerbose()) {
                    $output->writeln(sprintf("<info>Imported</info> %s", $activity->getTitle()));
                }

                if($limit && $nb >= $limit) {
                    break;
                }
            } catch (\Exception $e) {
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error> %s", $e->getMessage(), $activity->getTitle()));
                }
            }
        }

        if(!$dryrun) {
            $this->em->persist($source);
            $this->em->flush();
        }

        unlink($storeFile);

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check(Source $source) {
        parent::check($source);

        $path = $source->getParameter('path');

        if(!file_exists($path)) {
            throw new \Exception(sprintf("Le répertoire \"%s\" n'existe pas", $path));
        }

        if(!file_exists($path."/.git")) {
            throw new \Exception(sprintf("Le répertoire n'est pas un dépot Git", $path));
        }
    }

    protected function storeCsv(Source $source) {
        $fromDate = "1990-01-01";
        $path = $source->getParameter('path');

        if(isset($source->getUpdateParam()['date'])) {
            $fromDate = (new \DateTime($source->getUpdateParam()['date']))->modify('-15 days')->format('Y-m-d');
        }

        $storeFile = sprintf("%s/var/commits_%s_%s.csv", dirname(__FILE__), date("YmdHis"), uniqid());

        shell_exec(sprintf("%s/bin/git2csv.sh %s \"\" %s > %s", dirname(__FILE__), $path, $fromDate, $storeFile));

        $source->setUpdateParam(array('date' => date('Y-m-d')));

        return $storeFile;
    }

}

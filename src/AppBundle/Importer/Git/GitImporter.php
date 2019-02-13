<?php

namespace AppBundle\Importer\Git;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;

class GitImporter extends Importer
{
    protected $slugger;

    public function __construct($am, $em, $slugger)
    {
        parent::__construct($am, $em);
        $this->slugger = $slugger;
    }

    public function getName() {

        return 'Git';
    }

    public function getDescription() {

        return "Récupère les commits d'un dêpot git distant ou à partir d'un dossier local";
    }

    public function getParameters() {

        return array(
            'path' => array("required" => true, "label" => "Chemin", "help" => "Chemin vers le dossier du projet git"),
            'name' => array("required" => false, "label" => "Nom", "help" => "Nom du dépot git (optionelle, calculer automatiquement)"),
            'author' => array("required" => false, "label" => "Auteur", "help" => "Filtrer l'auteur des commits, grâce à une expression régulière (optionelle)"),
            'branch' => array("required" => false, "label" => "Branche", "help" => "Récupère seulement les commits de la branche"),
        );
    }

    public function updateParameters(Source $source, $parameters) {
        parent::updateParameters($source, $parameters);

        if(!$source->getParameter('name')) {
            $source->setParameter('name', preg_replace("/.git$/", "", basename($source->getParameter('path'))).".git");
        }
    }

    public function updateTitle(Source $source) {
        $source->setTitle($source->getParameter('path'));
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import git commit in %s</comment>", $source->getTitle()));

        if($this->isRemote($source)) {
            $this->cloneAndFetchRepository($source);
        }

        $storeFile = $this->storeCsv($source);

        $repositoryName = $source->getParameter('name');

        $nb = 0;

        foreach(file($storeFile) as $line) {
            $datas = str_getcsv($line, ";", '"');

            $authorEmail = isset($datas[3]) ? trim(preg_replace("/^.+<(.+)>$/", '\1', $datas[3])) : null;

            if($source->getParameter('author') && !preg_match("/".$source->getParameter('author')."/", $authorEmail)) {
                continue;
            }

            $content = isset($datas[6]) ? str_replace('\n', "\n", trim($datas[6])) : null;

            if($datas[2] && preg_match("/^Merge: ([a-z0-9]+) ([a-z0-9]+)$/", $datas[2], $matches)) {
                if($content) {
                    $content .= "\n\n";
                }
                $content .= shell_exec(sprintf("cd %s; git log %s..%s --oneline", $this->getPath($source), $matches[1], $matches[2]));
            }

            try {
                $activity = new Activity();
                $activity->setExecutedAt(isset($datas[4]) ? new \DateTime(trim($datas[4])) : null);
                $activity->setTitle(isset($datas[5]) ? trim($datas[5]) : null);
                $activity->setContent($content);

                $type = new ActivityAttribute();
                $type->setName("Type");
                $type->setValue("Commit");

                $repository = new ActivityAttribute();
                $repository->setName("Repository");
                $repository->setValue($repositoryName);

                $author = new ActivityAttribute();
                $author->setName("Author");
                $author->setValue($authorEmail);

                $branchName = $datas[1];

                $branch = new ActivityAttribute();
                $branch->setName("Branch");
                $branch->setValue($branchName);

                $activity->addAttribute($type);
                $activity->addAttribute($repository);
                $activity->addAttribute($author);
                $activity->addAttribute($branch);

                $this->am->addFromEntity($activity, $checkExist);

                $this->em->persist($type);
                $this->em->persist($repository);
                $this->em->persist($author);
                $this->em->persist($branch);

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

        $path = $this->getPath($source);

        if(!$this->isRemote($source) && !file_exists($path)) {
            throw new \Exception(sprintf("Le répertoire \"%s\" n'existe pas", $path));
        }

        if(!$this->isRemote($source) && !file_exists($path."/.git")) {
            throw new \Exception(sprintf("Le répertoire n'est pas un dépot Git", $path));
        }
    }

    public function isRemote(Source $source) {

        return (bool) preg_match("|^[a-zA-Z]+://|", $source->getParameter('path'));
    }

    protected function cloneAndFetchRepository(Source $source) {
        $uri = $source->getParameter('path');
        $dirName = $this->slugger->slugify($source->getParameter('path'));
        $path = $this->getVarDir()."/".$dirName;
        if(!file_exists($path."/HEAD")) {
            shell_exec(sprintf("cd %s; git clone %s %s --bare > /dev/null 2>&1", $this->getVarDir(), $uri, $dirName));
        }

        shell_exec(sprintf("cd %s; git fetch origin *:* -f > /dev/null 2>&1", $path));
    }

    protected function getPath($source) {

        if(!$source->getParameter('path') && $source->getSource()) {

            return $source->getSource();
        }

        return ($this->isRemote($source)) ? $this->getVarDir()."/".$this->slugger->slugify($source->getParameter('path')) : $source->getParameter('path');
    }

    protected function storeCsv(Source $source) {
        $fromDate = "1990-01-01";
        $path = $this->getPath($source);
        $branch = $source->getParameter('branch');

        if(isset($source->getUpdateParam()['date'])) {
            $fromDate = (new \DateTime($source->getUpdateParam()['date']))->modify('-15 days')->format('Y-m-d');
        }

        $storeFile = sprintf("%s/var/commits_%s_%s.csv", dirname(__FILE__), date("YmdHis"), uniqid());
        shell_exec(sprintf("%s/bin/git2csv.sh %s %s %s > %s", dirname(__FILE__), $path, $fromDate, $branch, $storeFile));

        $source->setUpdateParam(array('date' => date('Y-m-d')));

        return $storeFile;
    }

}

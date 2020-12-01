<?php

namespace AppBundle\Importer\Csv;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;
use Sabre\VObject;

class CsvImporter extends Importer
{
    public function getName() {

        return 'Csv';
    }

    public function getDescription() {

        return "Récupère les lignes d'un fichier CSV";
    }

    public function getParameters() {

        return array(
            'path' => array("required" => true, "label" => "Url ou chemin", "help" => "Url ou chemin du fichier csv"),
            'name' => array("required" => false, "label" => "Nom", "help" => "Nom (optionnelle)"),
            'date' => array("required" => true, "label" => "Colonne date", "help" => "Index ou nom de la colonne utilisée pour la date"),
            'title' => array("required" => true, "label" => "Colonne titre", "help" => "Index ou nom de la colonne utilisée pour le titre"),
            'content' => array("required" => false, "label" => "Colonne contenu", "help" => "Index ou nom de la colonne utilisée pour le contenu (optionnelle)"),
            'attributes' => array("required" => false, "label" => "Colonnes attributs", "help" => "Liste des colonnes à utiliser comme attribut (optionnelle)"),
        );
    }

    public function updateTitle(Source $source) {
        $source->setTitle($source->getParameter('path'));
    }

    public static function detectSeparator($file) {
        $handle = fopen($file, 'r');
        $buffer = fread($handle, 500);
        fclose($handle);

        $separator = ";";
        $virgule = explode(',', $buffer);
        $ptvirgule = explode(';', $buffer);
        $tabulation = explode('\t', $buffer);
        if (count($virgule) > count($ptvirgule) && count($virgule) > count($tabulation)) {
          $separator = ',';
        } else if (count($tabulation) > count($ptvirgule)) {
          $separator = '\t';
        }

        return $separator;
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import csv in %s</comment>", $source->getTitle()));

        $separator = self::detectSeparator($source->getParameter('path'));

        $nb = 0;
        $firstLine = true;
        $handle = fopen($source->getParameter('path'), "r");
        while (($data = fgetcsv($handle, 0, $separator)) !== false) {
            try {
                if($firstLine) {
                    $firstLine = false;
                    continue;
                }
                $dateExecutedAt = new \DateTime($data[$source->getParameter('date')]);
                $title = $data[$source->getParameter('title')];
                if($source->getParameter('content')) {
                $content = $data[$source->getParameter('content')];
                }

                $activity = new Activity();
                $activity->setExecutedAt($dateExecutedAt);
                $activity->setTitle($title);
                if(isset($content)) {
                    $activity->setContent($content);
                }
                if($source->getParameter('name')) {
                    $name = new ActivityAttribute();
                    $name->setName("Name");
                    $name->setValue($source->getParameter('name'));
                }
                $attributes = array();
                foreach($source->getParameter('attributes', array()) as $attributeName => $dataIndex) {
                    $attribute = new ActivityAttribute();
                    $attribute->setName($attributeName);
                    $attribute->setValue($data[$dataIndex]);
                    $attributes[] = $attribute;
                }

                if(isset($name)) {
                    $activity->addAttribute($name);
                }

                foreach($attributes as $attribute) {
                    $activity->addAttribute($attribute);
                }

                $this->am->addFromEntity($activity, $checkExist);

                if(isset($name)) {
                    $this->em->persist($name);
                }
                foreach($attributes as $attribute) {
                    $this->em->persist($attribute);
                }

                $this->em->persist($activity);

                if(!$dryrun) {
                    $this->em->flush($activity);
                }

                $nb++;

                if($limit && $nb > $limit) {
                    break;
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error>", $e->getMessage()));
                }
            }
        }
        fclose($handle);

        if(!$dryrun) {
            $this->em->persist($source);
            $this->em->flush();
        }

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check(Source $source) {
        parent::check($source);

    }

}

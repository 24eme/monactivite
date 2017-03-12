<?php

namespace AppBundle\Importer\ICalendar;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;
use Sabre\VObject;

class ICalendarImporter extends Importer
{
    public function getName() {

        return 'ICalendar';
    }

    public function getReader(Source $source) {

        return VObject\Reader::read(
            fopen($source->getSource(), 'r', false, stream_context_create(array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false))))
        );
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import icalendar event in %s</comment>", $source->getSourceProtected()));

        $vobject = $this->getReader($source);

        $nb = 0;

        foreach($vobject->VEVENT as $vevent) {
            try {
                $dateStart = $vevent->DTSTART->getDateTime();
                $dateEnd = $vevent->DTEND->getDateTime();

                $date = clone $dateStart;

                while($date->format('YmdHis') < $dateEnd->format("YmdHis")) {
                    if($date->format('Y-m-d') > date('Y-m-d')) {
                        break;
                    }

                    $dateExecutedAt = clone $date;
                    if($date->format('H:i:s') == "00:00:00") {
                        $dateExecutedAt = $dateExecutedAt->modify('+8 hours');
                    }

                    $title = $vevent->SUMMARY."";
                    $content = $vevent->DESCRIPTION."";

                    $activity = new Activity();
                    $activity->setExecutedAt($dateExecutedAt);
                    $activity->setTitle($title);
                    $activity->setContent($content);

                    $type = new ActivityAttribute();
                    $type->setName("Type");
                    $type->setValue("Event");

                    $activity->addAttribute($type);
                    $this->am->addFromEntity($activity, $checkExist);
                    $this->em->persist($type);
                    $this->em->persist($activity);

                    if(!$dryrun) {
                        $this->em->flush($activity);
                    }

                    if($output->isVerbose()) {
                        $output->writeln(sprintf("<info>Imported</info> %s", $activity->getTitle()));
                    }

                    $date = $date->modify("+1 day");
                    $nb++;
                }

                if($limit && $nb > $limit) {
                    break;
                }
            } catch (\Exception $e) {
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error> %s", $e->getMessage()));
                }
            }
        }

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

        $this->getReader($source);

        /*if(!file_exists($source->getSource())) {
            throw new \Exception(sprintf("File %s doesn't exist", $source->getSource()));
        }*/
    }

}

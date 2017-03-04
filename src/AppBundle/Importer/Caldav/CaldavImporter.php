<?php

namespace AppBundle\Importer\Caldav;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;
use Sabre\VObject;
use it\thecsea\simple_caldav_client\SimpleCalDAVClient;

class CaldavImporter extends Importer
{
    public function getName() {

        return 'Caldav';
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import caldav event in %s</comment>", $source->getSourceProtected()));

        $caldavClient = $this->getClient($source);

        foreach($caldavClient->findCalendars() as $calendar) {
            if(!preg_match("|".str_replace("/", "", $calendar->getURL())."|", str_replace("/", "", $source->getSource()))) {
                continue;
            }

            $caldavClient->setCalendar($calendar);
        }

        $nb = 0;

        foreach($caldavClient->getEvents() as $event) {
            $vobject = VObject\Reader::read($event->getData());
            foreach($vobject->VEVENT as $vevent) {
                try {
                    $date = $vevent->DTSTART->getDateTime();

                    if($date->format('Y-m-d') > date('Y-m-d')) {
                        continue;
                    }
                    if($date->format('H:i:s') == "00:00:00") {
                        $date = $date->modify('+8 hours');
                    }

                    $title = $vevent->SUMMARY."";
                    $content = $vevent->DESCRIPTION."";

                    $activity = new Activity();
                    $activity->setExecutedAt($date);
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

                    $nb++;

                    if($output->isVerbose()) {
                        $output->writeln(sprintf("<info>Imported</info> %s", $activity->getTitle()));
                    }

                    if($limit && $nb > $limit) {
                        break;
                    }
                } catch (\Exception $e) {
                    if($output->isVerbose()) {
                        $output->writeln(sprintf("<error>%s</error> %s", $e->getMessage(), $activity->getTitle()));
                    }
                }
            }
        }

        if(!$dryrun) {
            $this->em->persist($source);
            $this->em->flush();
        }

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    public function getClient($source) {
        $caldavClient = new SimpleCalDAVClient();

        $url = $source->getSource();
        $user = null;
        $password = null;

        if(preg_match("|^[a-z]+://(.+):(.+)@|", $url, $matches)) {
            $user = $matches[1];
            $password = $matches[2];
            $url = preg_replace("|^([a-z]+://).+@|", '\1', $url);
        }

        $caldavClient->connect($url, $user, $password);

        return $caldavClient;
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check(Source $source) {
        parent::check($source);

        $this->getClient($source);
    }

}

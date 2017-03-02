<?php

namespace AppBundle\Importer\Mail;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;

class MailImporter extends Importer
{
    protected $mailParser;

    public function getName() {

        return 'Mail';
    }

    public function __construct($am, $em, $mailParser)
    {
        parent::__construct($am, $em);

        $this->mailParser = $mailParser;
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import mails in %s</comment>", $source->getSource()));

        $mail = null;
        $start = false;
        $nb = 0;
        $handle = fopen($source->getSource(), "r");

        $nbLigne = 0;
        $lineToStart = 0;
        if(isset($source->getUpdateParam()['line'])) {
            $lineToStart = $source->getUpdateParam()['line'];
        }

        while (($line = fgets($handle)) !== false) {
            $nbLigne++;
            if($nbLigne <= $lineToStart) {
                continue;
            }
            if(preg_match('/^(From .?$|From - )/', $line)) {
                if($mail && $start) {
                    if($this->importMail($mail, $source, $output, $dryrun, $checkExist)) {
                        $nb++;
                        if($limit && $nb > $limit) {
                            break;
                        }
                    }
                }
                $mail = null;
                $start = true;
                continue;
            }

            $mail .= $line;
        }
        if($mail && $start) {
            if($this->importMail($mail, $source, $output, $dryrun, $checkExist)) { $nb++; }
        }

        fclose($handle);

        $source->setUpdateParam(array('line' => $nbLigne));
        if(!$dryrun) {
            $this->em->persist($source);
            $this->em->flush();
        }

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    protected function importMail($mail, Source $source, OutputInterface $output, $dryrun = false, $checkExist = true) {
        try {
            $parsedMail = @$this->mailParser->parse($mail);
        } catch(\Exception $e) {
            if($output->isVerbose()) {
                $output->writeln("<error>Error ".$e->getMessage()."</error>");
            }

            return false;
        }

        try {
            $subject = $parsedMail->getMail()->getHeaderField("Subject");
        } catch(\Exception $e) {
            $subject = null;
        }


        try {
            $date = $parsedMail->getMail()->getHeaderField("Date");
            $date = new \DateTime($date);
        } catch(\Exception $e) {
            if($output->isVerbose()) {
                $output->writeln("<error>Error ".$e->getMessage()."</error>");
            }

            return false;
        }

        $from = null;
        foreach($parsedMail->getAllEmailAddresses(array('from')) as $address) {
            $from = $address;
            break;
        }

        $to = null;
        foreach($parsedMail->getAllEmailAddresses(array('to')) as $address) {
            $to = $address;
            break;
        }

        $body = $parsedMail->getPrimaryContent();

        $body = str_replace("\r", "", $body);

        $activity = new Activity();
        $activity->setExecutedAt($date);
        $activity->setTitle($subject);
        $activity->setContent($body);

        $type = new ActivityAttribute();
        $type->setName("Type");
        $type->setValue("Mail");

        if($from) {
            $sender = new ActivityAttribute();
            $sender->setName("Sender");
            $sender->setValue($from);
        }

        if($to) {
            $recipient = new ActivityAttribute();
            $recipient->setName("Recipient");
            $recipient->setValue($to);
        }

        $activity->addAttribute($type);

        if(isset($sender)) {
            $activity->addAttribute($sender);
        }

        if(isset($recipient)) {
            $activity->addAttribute($recipient);
        }

        try {
            $this->am->addFromEntity($activity, $checkExist);

            $this->em->persist($type);
            if(isset($sender)) {
                $this->em->persist($sender);
            }
            if(isset($recipient)) {
                $this->em->persist($recipient);
            }
            $this->em->persist($activity);

            if(!$dryrun) {
                $this->em->flush($activity);
            }
            if($output->isVerbose()) {
                $output->writeln(sprintf("<info>Imported</info>;%s", $activity->getTitle()));
            }
        } catch (\Exception $e) {
            if($output->isVerbose()) {
                $output->writeln(sprintf("<error>%s</error>;%s", $e->getMessage(), $activity->getTitle()));
            }

            return false;
        }

        return true;
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check(Source $source) {
        parent::check($source);

        if(!file_exists($source->getSource())) {
            throw new \Exception(sprintf("File %s doesn't exist", $source->getSource()));
        }

        $line = "";
        $handle = fopen($source->getSource(), "r");
        for($i=1; $i<20; $i++) { $line .= fgets($handle); }
        fclose($handle);

        if(!preg_match("/Message-ID/i", $line)) {
           throw new \Exception(sprintf("This file is not a mail file : %s", $source->getSource()));
        }
    }

}

<?php

namespace AppBundle\Importer\Mail;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;

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

    public function run($source, $sourceName = null, OutputInterface $output, $dryrun = false) {
        $output->writeln(sprintf("<comment>Started import mails in %s</comment>", $source));

        $mail = null;
        $start = false;
        $nb = 0;
        $handle = fopen($source, "r");

        while (($line = fgets($handle)) !== false) {
            if(preg_match('/^(From .?$|From - )/', $line)) {
                if($mail && $start) {
                    if($this->importMail($mail, $source, $sourceName, $output, $dryrun)) { $nb++; }
                }
                $mail = null;
                $start = true;
                continue;
            }

            $mail .= $line;
        }

        if($mail && $start) {
            if($this->importMail($mail, $source, $sourceName, $output, $dryrun)) { $nb++; }
        }

        fclose($handle);

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    protected function importMail($mail, $source, $sourceName, OutputInterface $output, $dryrun = false) {
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

        $date = $parsedMail->getMail()->getHeaderField("Date");

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

        $activity = new Activity();
        $activity->setExecutedAt(new \DateTime($date));
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
            $this->am->addFromEntity($activity);

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

    public function check($source) {
        parent::check($source);

        if(!file_exists($source)) {
            throw new \Exception(sprintf("File %s doesn't exist", $source));
        }

        $line = "";
        $handle = fopen($source, "r");
        for($i=1; $i<20; $i++) { $line .= fgets($handle); }
        fclose($handle);

        if(!preg_match("/Message-ID/i", $line)) {
           throw new \Exception(sprintf("This file is not a mail file : %s", $source)); 
        }
    }

} 
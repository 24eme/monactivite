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

    public function getDescription() {

        return "Récupère les mails à partir d'un fichier (fonctionne par exemple avec Thunderbird)";
    }

    public function getParameters() {

        return array(
            'path' => array("required" => true, "label" => "Chemin", "help" => "Chemin vers le fichier contenant les mails"),
            'sender' => array("required" => false, "label" => "Expéditeur", "help" => "Filtrer l'email de l'expéditeur (optionnelle)"),
        );
    }

    public function updateTitle(Source $source) {
        $source->setTitle($source->getParameter('path'));
    }

    public function __construct($am, $em, $mailParser)
    {
        parent::__construct($am, $em);

        $this->mailParser = $mailParser;
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import mails in %s</comment>", $source->getTitle()));

        $mail = null;
        $start = false;
        $nb = 0;

        $path = ($source->getParameter("path")) ? $source->getParameter("path") : $source->getSource();
        $handle = fopen($path, "r");

        $nbLigne = 0;
        $lineToStart = 0;
        if(isset($source->getUpdateParam()['line']) && !$dryrun) {
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

        $from = null;
        foreach($parsedMail->getAllEmailAddresses(array('from')) as $address) {
            $from = $address;
            break;
        }

        if($source->getParameter('sender') && !preg_match("/".$source->getParameter('sender')."/", $from)) {

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

        $to = null;
        $tos = array();
        foreach($parsedMail->getAllEmailAddresses(array('to')) as $address) {
            $tos[$address] = $address;
            if($to) {
                continue;
            }
            $to = $address;
        }

        $ccs = array();
        foreach($parsedMail->getAllEmailAddresses(array('cc')) as $address) {
            $ccs[$address] = $address;
        }

        $body = null;
        $body .= $parsedMail->getPrimaryContent();

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

        if(count($tos)) {
            $toAttribute = new ActivityAttribute();
            $toAttribute->setName("To");
            $toAttribute->setValue(implode(", ", $tos));
        }

        if(count($ccs)) {
            $ccAttribute = new ActivityAttribute();
            $ccAttribute->setName("Cc");
            $ccAttribute->setValue(implode(", ", $ccs));
        }

        $activity->addAttribute($type);

        if(isset($sender)) {
            $activity->addAttribute($sender);
        }

        if(isset($recipient)) {
            $activity->addAttribute($recipient);
        }

        if(isset($toAttribute)) {
            $activity->addAttribute($toAttribute);
        }

        if(isset($ccAttribute)) {
            $activity->addAttribute($ccAttribute);
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
            if(isset($toAttribute)) {
                $this->em->persist($toAttribute);
            }
            if(isset($ccAttribute)) {
                $this->em->persist($ccAttribute);
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

        $path = ($source->getParameter("path")) ? $source->getParameter("path") : $source->getSource();

        if(!is_readable($path)) {
            throw new \Exception(sprintf("File %s doesn't exist or is not readable", $path));
        }

        $line = "";
        $handle = fopen($path, "r");
        for($i=1; $i<20; $i++) { $line .= fgets($handle); }
        fclose($handle);

        if(!preg_match("/Message-ID/i", $line)) {
           throw new \Exception(sprintf("This file is not a mail file : %s", $path));
        }
    }

}

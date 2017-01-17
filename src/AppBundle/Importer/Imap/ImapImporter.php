<?php

namespace AppBundle\Importer\Imap;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Importer\Mail\MailImporter;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;
use Zend\Mail\Protocol;

class ImapImporter extends Importer
{
    public function getName() {

        return 'Imap';
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import mails by imap in %s</comment>", $source->getSource()));

        $mailbox = $this->getConnexion($source);

        $date = null;
        if(isset($source->getUpdateParam()['date'])) {
            $date = new \DateTime($source->getUpdateParam()['date']);
            $date = $date->modify("-7 days");
        }

        if($date) {
            $dateImap = $date->format("d M Y");
            $mailIds = $mailbox->searchMailbox("SINCE \"$date\"");
        } else {
            $mailIds = $mailbox->searchMailbox("ALL");
        }

        $nb = 0;

        foreach($mailIds as $mailId) {
            $mail = $mailbox->getMail($mailId);

            $activity = new Activity();
            $activity->setExecutedAt(new \DateTime($mail->headers->Date));
            $activity->setTitle($mail->subject);
            $activity->setContent($mail->textPlain);

            $type = new ActivityAttribute();
            $type->setName("Type");
            $type->setValue("Mail");

            $sender = new ActivityAttribute();
            $sender->setName("Sender");
            $sender->setValue($mail->fromAddress);

            foreach($mail->to as $email => $name) {
                $recipient = new ActivityAttribute();
                $recipient->setName("Recipient");
                $recipient->setValue($email);
                break;
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

                $nb++;

                if($output->isVerbose()) {
                    $output->writeln(sprintf("<info>Imported</info>;%s", $activity->getTitle()));
                }
            } catch (\Exception $e) {
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error>;%s", $e->getMessage(), $activity->getTitle()));
                }
            }
        }

        if(!$dryrun) {
            $source->setUpdateParam(array('date' => $date->format('Y-m-d')));
            $this->em->persist($source);
            $this->em->flush();
        }

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    public function getConnexion($source) {
        if(!preg_match("/^(.+):(.+)@(.+)$/", $source->getSource(), $matches)) {

            return false;
        }

        $host = $matches[3];
        $username = $matches[1];
        $password = $matches[2];

        $mailbox = new \PhpImap\Mailbox($host, $username, $password);

        return $mailbox;
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check(Source $source) {
        parent::check($source);

        if(!$this->getConnexion($source)) {
            throw new \Exception(sprintf("La connexion à l'imap ne fonctionne pas  : %s", $source->getSource()));
        }
    }


}

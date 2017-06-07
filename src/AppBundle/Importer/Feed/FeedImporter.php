<?php

namespace AppBundle\Importer\Feed;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;

class FeedImporter extends Importer
{
    protected $feedParser;

    public function getName() {

        return 'Feed';
    }

    public function getDescription() {

        return "Récupère l'activité à partir d'un flux RSS ou Atom";
    }

    public function getParameters() {

        return array(
            'uri' => array("required" => true, "label" => "Uri", "help" => "Url ou chemin vers un flux rss"),
            'name' => array("required" => true, "label" => "Name", "help" => "Nom du flux (optionnelle)"),
        );
    }

    public function updateTitle(Source $source) {
        $source->setTitle($source->getParameter('uri'));
    }

    public function __construct($am, $em, $feedParser)
    {
        parent::__construct($am, $em);

        $this->feedParser = $feedParser;
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {
        $output->writeln(sprintf("<comment>Started import feed %s</comment>", $source->getTitle()));

        $parser = $this->getParser($source);

        $feed = $parser->execute();

        $nb = 0;

        foreach($feed->getItems() as $item) {
            $author = $item->getAuthor() ? $item->getAuthor() : null;

            $activity = new Activity();
            $activity->setExecutedAt($item->getDate());
            $activity->setTitle($item->getTitle());
            $activity->setContent($item->getContent());

            if($source->getParameter('name')) {
                $name = new ActivityAttribute();
                $name->setName("Name");
                $name->setValue($source->getParameter('name'));
            }

            if($item->getAuthor()) {
                $author = new ActivityAttribute();
                $author->setName("Author");
                $author->setValue($item->getAuthor());
            }

            if(isset($name)) {
                $activity->addAttribute($name);
            }

            if(isset($author)) {
                $activity->addAttribute($author);
            }

            try {
                $this->am->addFromEntity($activity, $checkExist);

                if(isset($name)) {
                    $this->em->persist($name);
                }
                if(isset($author)) {
                    $this->em->persist($author);
                }
                $this->em->persist($activity);

                if(!$dryrun) {
                    $this->em->flush($activity);
                }

                $nb++;

                if($limit && $nb > $limit) {
                    break;
                }

                if($output->isVerbose()) {
                    $output->writeln(sprintf("<info>Imported</info>;%s;%s", $item->getDate()->format('c'), $item->getTitle()));
                }

            } catch (\Exception $e) {
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error>;%s;%s", $e->getMessage(), $item->getDate()->format('c'), $item->getTitle()));
                }
            }

        }

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    public function getParser($source) {
        if(preg_match("|^file://|", $source->getParameter('uri'))) {
            $content = file_get_contents($source->getParameter('uri'));

            return $this->feedParser->getParser(
                $source->getParameter('uri'),
                file_get_contents($source->getParameter('uri')),
                mb_detect_encoding($content)
            );
        }

        $resource = $this->feedParser->download($source->getParameter('uri'));

        return $this->feedParser->getParser(
            $resource->getUrl(),
            $resource->getContent(),
            $resource->getEncoding()
        );
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check(Source $source) {
        parent::check($source);

        try {
            $resource = $this->feedParser->download($source->getSource());
            $parser = $this->feedParser->getParser($resource->getUrl(), $resource->getContent(), $resource->getEncoding());
        } catch(\Exception $e) {

            throw new \Exception(sprintf("Feed Url %s isn't valid : %s", $source->getSource(), $e->getMessage()));
        }
    }

}

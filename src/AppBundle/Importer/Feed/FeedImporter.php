<?php

namespace AppBundle\Importer\Feed;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;

class FeedImporter extends Importer
{
    protected $feedParser;

    public function getName() {

        return 'Feed';
    }

    public function __construct($am, $em, $feedParser)
    {
        parent::__construct($am, $em);

        $this->feedParser = $feedParser;
    }

    public function run($source, $sourceName = null, OutputInterface $output, $dryrun = false) {
        $output->writeln(sprintf("<comment>Started import feed %s</comment>", $source));

        $resource = $this->feedParser->download($source);

        $parser = $this->feedParser->getParser(
            $resource->getUrl(),
            $resource->getContent(),
            $resource->getEncoding()
        );

        $feed = $parser->execute();
        
        $nb = 0;
        
        foreach($feed->getItems() as $item) {
            $author = $item->getAuthor() ? $item->getAuthor() : null;
            
            $activity = new Activity();
            $activity->setExecutedAt($item->getDate());
            $activity->setTitle($item->getTitle());
            $activity->setContent($item->getContent());

            if($sourceName) {
                $name = new ActivityAttribute();
                $name->setName("Name");
                $name->setValue($sourceName);
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
                $this->am->addFromEntity($activity);

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

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check($source) {
        parent::check($source);

        try {
            $resource = $this->feedParser->download($source);
            $parser = $this->feedParser->getParser($resource->getUrl(), $resource->getContent(), $resource->getEncoding());
        } catch(\Exception $e) {

            throw new \Exception(sprintf("Feed Url %s isn't valid : %s", $source, $e->getMessage()));
        }
    }

} 
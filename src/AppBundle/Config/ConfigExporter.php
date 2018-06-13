<?php

namespace AppBundle\Config;

use AppBundle\Entity\Source;
use AppBundle\Entity\Filter;
use AppBundle\Entity\Tag;
use Symfony\Component\Yaml\Yaml;

class ConfigExporter
{
    protected $em;

    public function __construct($em) {
        $this->em = $em;
    }

    public function getYaml() {

        $sources = $this->em->getRepository('AppBundle:Source')->findAll();
        $tags = $this->em->getRepository('AppBundle:Tag')->findAll();
        $filters = $this->em->getRepository('AppBundle:Filter')->findAll();

        return $this->toYaml(array_merge($sources, $tags, $filters));
    }

    public function toArray($entities) {
        $config = array();

        $config["sources"] = array();
        $config["tags"] = array();
        $config["filters"] = array();

        foreach($entities as $entity) {
            if($entity instanceof Source) {
                $config["sources"][] = $entity->toConfig();
            }
            if($entity instanceof Tag) {
                $config["tags"][] = $entity->toConfig();
            }
            if($entity instanceof Filter) {
                $config["filters"][] = $entity->toConfig();
            }
        }

        return $config;
    }

    public function toYaml($entities) {

        return Yaml::dump($this->toArray($entities), 2, 4);
    }
}

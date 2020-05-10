<?php

namespace Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use AppBundle\Entity\Source;
use AppBundle\Entity\Filter;
use AppBundle\Entity\Tag;
use Symfony\Component\Yaml\Yaml;

class ConfigExporterTest extends KernelTestCase
{
    private $container;

    public function setUp() : void
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testExport()
    {
        $configExporter = $this->container->get('app.config.exporter');

        $source = new Source();
        $source->setImporter("Git");
        $source->setParameter('path', "file://user_test:user_password@test_path");
        $source->setParameter('branch', "master");

        $tag = new Tag();
        $tag->setName("Commit");
        $tag->setColor("#fff");
        $tag->setColorText("#000");
        $tag->setIcon("cog");

        $filter = new Filter();
        $filter->setQuery("Type:Commit");
        $filter->setTag($tag);

        $entities = array($source, $source, $filter, $tag);

        $config = $configExporter->toArray($entities);

        $this->assertCount(3, $config);

        $this->assertCount(2, $config["sources"]);
        $this->assertCount(1 + count($source->getParameters()), $config["sources"][0]);
        $this->assertSame($config["sources"][0]["importer"], $source->getImporter());
        $this->assertSame($config["sources"][0]["path"], "file://".substr(hash("sha512", "user_test:user_password"), 0, 10)."@test_path");
        $this->assertSame($config["sources"][0]["branch"], $source->getParameter('branch'));

        $this->assertCount(1, $config["tags"]);
        $this->assertCount(4, $config["tags"][0]);
        $this->assertSame($config["tags"][0]["name"], $tag->getName());
        $this->assertSame($config["tags"][0]["color"], $tag->getColor());
        $this->assertSame($config["tags"][0]["color_text"], $tag->getColorText());
        $this->assertSame($config["tags"][0]["icon"], $tag->getIcon());

        $this->assertCount(1, $config["filters"]);
        $this->assertCount(2, $config["filters"][0]);
        $this->assertSame($config["filters"][0]["query"], $filter->getQuery());
        $this->assertSame($config["filters"][0]["tag"], $tag->getName());

        @$this->assertNotEmpty($configExporter->toYaml($entities), Yaml::dump($config, 2, 4));
    }

}

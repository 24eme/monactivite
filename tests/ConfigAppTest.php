<?php

namespace Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use AppBundle\Config\ConfigApp;

class ConfigAppTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();
        $this->container = self::$kernel->getContainer();
    }

    public function testConfigs()
    {
        $config = new ConfigApp();

        $this->assertSame($config->getViewMode(), $this->container->getParameter('viewmode'));
    }



}

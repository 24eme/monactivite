<?php

namespace AppBundle\Config;

use Symfony\Component\Yaml\Yaml;

class ConfigApp
{
    private static $_instance = null;
    public $config = array();

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new ConfigApp();
        }
        return self::$_instance;
    }

    public function getConfigFile()
    {
        return __DIR__."/../../../app/config/parameters.yml";
    }

    public function __construct() {
        if(file_exists($this->getConfigFile())) {
            $parameters = Yaml::parseFile($this->getConfigFile());
            $this->config = $parameters['parameters'];
        }
    }

    public function getViewMode() {
        if(isset($this->config['viewmode'])) {

            return $this->config['viewmode'];
        }

        return 'daily';
    }


}

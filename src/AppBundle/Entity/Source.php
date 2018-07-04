<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Source
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Source
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="importer", type="string", length=255)
     */
    private $importer;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="json_array", nullable=true)
     */
    private $parameters;

    /**
     * @var string
     *
     * @ORM\Column(name="update_param", type="json_array", nullable=true)
     */
    private $updateParam;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set importer
     *
     * @param string $importer
     * @return Source
     */
    public function setImporter($importer)
    {
        $this->importer = $importer;

        return $this;
    }

    /**
     * Get importer
     *
     * @return string
     */
    public function getImporter()
    {
        return $this->importer;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Source
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Source
     */
    public function setTitle($title)
    {
        $this->title = $this->protect($title);

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set $paramaters
     *
     * @param array $paramaters
     * @return Source
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setParameter($name, $value) {

        $this->parameters[$name] = $value;
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function getParameter($name) {
        if(!isset($this->parameters[$name])) {

            return null;
        }

        return $this->parameters[$name];
    }

    /**
     * Set updateParam
     *
     * @param array $updateParam
     * @return Source
     */
    public function setUpdateParam($updateParam)
    {
        $this->updateParam = $updateParam;

        return $this;
    }

    /**
     * Get updateParam
     *
     * @return array
     */
    public function getUpdateParam()
    {
        return $this->updateParam;
    }

    public function getProtectedParameter($name, $hidePassword = true) {

        return $this->protect($this->getParameter($name, $hidePassword));
    }

    protected function protect($value, $hidePassword = true) {
        if(!preg_match("|://(.+:.*)@|", $value, $matches)) {
            return $value;
        }
        $auth = null;

        if(!$hidePassword) {
            $auth = substr(hash("sha512", $matches[1]), 0, 10);
        }

        return preg_replace("|(://).+:.*(@)|", '${1}'.$auth.'${2}', $value);
    }

    public function toConfig() {
        $config = array(
            'importer' => $this->importer,
        );

        if(!$this->getParameter('path')) {
            $config['path'] = $this->protect($this->getSource(), false);
        }

        foreach($this->getParameters() as $key => $value) {
            $config[$key] = $this->protect($value, false);
        }

        return $config;
    }

}

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
     * @ORM\Column(name="source", type="string", length=255)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

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
     * Set name
     *
     * @param string $name
     * @return Source
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
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
}

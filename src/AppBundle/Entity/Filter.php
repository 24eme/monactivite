<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Filter
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Filter
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
     * @ORM\Column(name="query", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $query;

    /**
     * @ORM\ManyToOne(targetEntity="Tag")
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     * @Assert\Type(type="AppBundle\Entity\Tag")
     * @Assert\Valid()
     */
    private $tag;

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
     * Set query
     *
     * @param string $query
     * @return Filter
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set tag
     *
     * @param \AppBundle\Entity\Tag $tag
     * @return Filter
     */
    public function setTag(\AppBundle\Entity\Tag $tag = null)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return \AppBundle\Entity\Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    public function __toString() {

        return $this->getQuery();
    }

    public function getTags() {

        return null;
    }

    public function toConfig() {

        return array(
            'query' => $this->getQuery(),
            'tag' => $this->getTag()->getName(),
        );
    }
}

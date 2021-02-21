<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ActivityRepository")
 */
class Activity
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
     * @ORM\Column(name="title", type="string", length=1024)
     */
    private $title;


    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="float", nullable=true)
     */
    private $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="executed_at", type="datetime")
     */
    private $executedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @ORM\OneToMany(targetEntity="ActivityAttribute", mappedBy="activity")
     */
    protected $attributes;

    /**
     * @ORM\ManyToMany(targetEntity="Tag")
     * @ORM\JoinTable(name="activities_tags")
     **/
    private $tags;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attributes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->deleted = false;
    }

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
     * Set title
     *
     * @param string $title
     * @return Activity
     */
    public function setTitle($title)
    {
        $this->title = $title;

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
     * Set content
     *
     * @param string $content
     * @return Activity
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Activity
     */
    public function setValue($value)
    {
        if(is_null($value) || $value === "" || !preg_match("/[0-9]+/", $value)) {

            $value = null;
        }

        if(!is_null($value)) {
            $value = (float) $value;
        }

        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {

        return $this->value;
    }

    /**
     * Set executedAt
     *
     * @param \DateTime $executedAt
     * @return Activity
     */
    public function setExecutedAt($executedAt)
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    /**
     * Get executedAt
     *
     * @return \DateTime 
     */
    public function getExecutedAt()
    {
        return $this->executedAt;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Activity
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Add attributes
     *
     * @param \AppBundle\Entity\ActivityAttribute $attributes
     * @return Activity
     */
    public function addAttribute(\AppBundle\Entity\ActivityAttribute $attributes)
    {
        $this->attributes[] = $attributes;
        $attributes->setActivity($this);

        return $this;
    }

    /**
     * Remove attributes
     *
     * @param \AppBundle\Entity\ActivityAttribute $attributes
     */
    public function removeAttribute(\AppBundle\Entity\ActivityAttribute $attributes)
    {
        $this->attributes->removeElement($attributes);
    }

    /**
     * Get attributes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttributesHtml() {
        $text = "";
        foreach($this->getAttributes() as $attribute) {
            $text .= "<small class='text-muted'>".$attribute->getName()."&nbsp;:</small>" . "&nbsp;" . $attribute->getValue() . "<br />";
        }

        return $text;
    }

    /**
     * Add tags
     *
     * @param \AppBundle\Entity\Tag $tags
     * @return Activity
     */
    public function addTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags[] = $tag;

        if($tag->getSpecial() == Tag::SPECIAL_DELETED) {
            $this->deleted = true;
        }

        return $this;
    }

    /**
     * Remove tags
     *
     * @param \AppBundle\Entity\Tag $tags
     */
    public function removeTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags->removeElement($tag);

        if($tag->getSpecial() == Tag::SPECIAL_DELETED) {
            $this->deleted = false;
        }
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function getTagsReverse()
    {
        $tags = array();

        foreach($this->tags as $tag) {
           $tags[] = $tag; 
        }
        return array_reverse($tags);
    }

    public function __toString() {

        return $this->getTitle();
    }

    public function getKeyDate($viewMode = 'daily') {
        $date = clone $this->getExecutedAt();

        if($viewMode == "monthly") {

            return $date->format('Y-m');
        }

        return $date->modify('-4 hours')->format('Y-m-d');
    }

    public function toCSV() {
        $attributes = array();

        foreach($this->getAttributes() as $att) {
            $attributes[] = $att->getName().":".$att->getValue();
        }

        $tags = array();

        foreach($this->getTags() as $tag) {
            $tags[] = $tag->getName();
        }

        return $this->getExecutedAt()->format('Y-m-d H:i:s').";".implode(",", $tags).";\"".str_replace("\n", '\n', $this->getTitle())."\";".implode(",", $attributes).";\"".str_replace("\n", '\n', $this->getContent())."\";".$this->getValue();
    }
}

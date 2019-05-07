<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Tag;

class LoadTagData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $tagCommit = $manager->getRepository('AppBundle:Tag')->findOneByName('Commit');
        if(!$tagCommit) {
            $tagCommit = new Tag();
            $manager->persist($tagCommit);
            $this->addReference('tag-commit', $tagCommit);
        }

        $tagCommit->setName('Commit');
        $tagCommit->setIcon('cog');
        $tagCommit->setColor(null);
        $tagCommit->setSpecial(Tag::SPECIAL_TYPE);

        $tagMail = $manager->getRepository('AppBundle:Tag')->findOneByName('Mail');
        if(!$tagMail) {
            $tagMail = new Tag();
            $manager->persist($tagMail);
            $this->addReference('tag-mail', $tagMail);
        }

        $tagMail->setName('Mail');
        $tagMail->setIcon('envelope');
        $tagMail->setColor(null);
        $tagMail->setSpecial(Tag::SPECIAL_TYPE);

        $tagEvent = $manager->getRepository('AppBundle:Tag')->findOneByName('Event');
        if(!$tagEvent) {
            $tagEvent = new Tag();
            $manager->persist($tagEvent);
            $this->addReference('tag-event', $tagEvent);
        }

        $tagEvent->setName('Event');
        $tagEvent->setIcon('calendar');
        $tagEvent->setColor(null);
        $tagEvent->setSpecial(Tag::SPECIAL_TYPE);

        $tagDeleted = $manager->getRepository('AppBundle:Tag')->findOneByName('Deleted');
        if(!$tagDeleted) {
            $tagDeleted = new Tag();
            $manager->persist($tagDeleted);
        }

        $tagDeleted->setName('Deleted');
        $tagDeleted->setIcon('trash');
        $tagDeleted->setColor(null);
        $tagDeleted->setSpecial(Tag::SPECIAL_DELETED);

        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}

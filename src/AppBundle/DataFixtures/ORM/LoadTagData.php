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
        $tagCommit = new Tag();
        $tagCommit->setName('Commit');
        $tagCommit->setIcon('cog');
        $tagCommit->setColor(null);
        $manager->persist($tagCommit);

        $tagMail = new Tag();
        $tagMail->setName('Mail');
        $tagMail->setIcon('envelope');
        $tagMail->setColor(null);
        $manager->persist($tagMail);

        $manager->flush();

        $this->addReference('tag-commit', $tagCommit);
        $this->addReference('tag-mail', $tagMail);
    }

    public function getOrder()
    {
        return 1;
    }
}

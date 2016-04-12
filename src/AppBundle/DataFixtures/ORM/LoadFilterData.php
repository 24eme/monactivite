<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Filter;

class LoadFilterData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $filterCommit = new Filter();
        $filterCommit->setQuery('Type:Commit');
        $filterCommit->setTag($this->getReference('tag-commit'));
        $manager->persist($filterCommit);

        $filterMail = new Filter();
        $filterMail->setQuery('Type:Mail');
        $filterMail->setTag($this->getReference('tag-mail'));
        $manager->persist($filterMail);

        $filterEvent = new Filter();
        $filterEvent->setQuery('Type:Event');
        $filterEvent->setTag($this->getReference('tag-event'));
        $manager->persist($filterEvent);

        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}

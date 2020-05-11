<?php

namespace AppBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Filter;

class FilterFixtures extends Fixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if($this->hasReference('tag-commit')) {
            $filterCommit = new Filter();
            $filterCommit->setQuery('Type:Commit');
            $filterCommit->setTag($this->getReference('tag-commit'));
            $manager->persist($filterCommit);
        }

        if($this->hasReference('tag-mail')) {
            $filterMail = new Filter();
            $filterMail->setQuery('Type:Mail');
            $filterMail->setTag($this->getReference('tag-mail'));
            $manager->persist($filterMail);
        }

        if($this->hasReference('tag-event')) {
            $filterEvent = new Filter();
            $filterEvent->setQuery('Type:Event');
            $filterEvent->setTag($this->getReference('tag-event'));
            $manager->persist($filterEvent);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}

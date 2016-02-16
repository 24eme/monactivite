<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="timeline")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Activity');

        $activitiesByDates = array();

        $dateFrom = new \DateTime();
        $dateTo = clone $dateFrom;
        $dateTo->modify('-7 days');

        $activities = $repo->findByDatesInterval($dateFrom, $dateTo);

        foreach($activities as $activity) {
            $keyDate = $activity->getExecutedAt()->format('Y-m-d');
            if(!array_key_exists($keyDate, $activitiesByDates)) {
                $activitiesByDates[$keyDate] = array('activites' => array(), 'tags' => array());
            }
            $activitiesByDates[$keyDate]['activities'][$activity->getId()] = $activity;
            foreach($activity->getTags() as $tag) {
                if(!array_key_exists($tag->getId(), $activitiesByDates[$keyDate]['tags'])) {
                    $activitiesByDates[$keyDate]['tags'][$tag->getId()] = array('nb' => 0, 'entity' => $tag);
                }
                $activitiesByDates[$keyDate]['tags'][$tag->getId()]['nb'] += 1;
            }
        }

        foreach($activitiesByDates as $activitiesByDate) {
            usort($activitiesByDate['tags'], "\AppBundle\Controller\DefaultController::sortTagByNb");
        }

        $tags = $em->getRepository('AppBundle:Tag')->findAll();

        return $this->render('default/index.html.twig', array('activitiesByDates' => $activitiesByDates, 'tags' => $tags));
    }

    /**
     * @Route("/update", name="update")
     */
    public function updateAction()
    {

        return $this->render('default/update.html.twig');
    }

    public static function sortTagByNb($a, $b) {

        return $a['nb'] < $b['nb'];
    }
}

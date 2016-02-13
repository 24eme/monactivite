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

        for($i = 0; $i < 40; $i++) {
            $date = new \DateTime(date('Y-m-d 05:00:00'));
            $date->modify("- ".$i."days");
            $activities = $repo->findByDate($date);
            if(count($activities) > 0) {
                $activitiesByDates[$date->format('Y-m-d')] = $repo->findByDate($date);
            }
        }

        $tags = $em->getRepository('AppBundle:Tag')->findAll();

        return $this->render('default/index.html.twig', array('activitiesByDates' => $activitiesByDates, 'tags' => $tags));
    }

    public function listAction($activities, $title, $readOnly = false) {
        $tags = array();

        foreach($activities as $activity) {
            foreach($activity->getTags() as $tag) {
                if(!isset($tags[$tag->getId()])) {
                    $tags[$tag->getId()] = array('nb' => 0  );
                }
                $tags[$tag->getId()]['nb'] += 1;
                $tags[$tag->getId()]['entity'] = $tag;
            }
        }

        usort($tags, "\AppBundle\Controller\DefaultController::sortTagByNb");

        return $this->render('Activity/list.html.twig', array('activities' => $activities, 'tags' => $tags, 'title' => $title, 'readOnly' => $readOnly));
    }

    public static function sortTagByNb($a, $b) {

        return $a['nb'] < $b['nb'];
    }
}

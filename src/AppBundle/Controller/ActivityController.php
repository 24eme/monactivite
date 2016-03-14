<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Filter controller.
 *
 * @Route("/activity")
 */
class ActivityController extends Controller
{
    /**
     * @Route("/list", name="activity_list")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Activity');

        $nbDays = $request->get('nb', 10);
        $dateFrom = $request->get('date', new \DateTime());
        $query = $request->get('q', null);

        $activitiesByDates = array();

        $dateFrom = new \DateTime();

        $dateTo = new \DateTime();
        $dateTo->modify('-6 month');

        $activities = $repo->findByDatesInterval($dateFrom, $dateTo, $nbDays, $query);

        foreach($activities as $activity) {
            $keyDate = $activity->getKeyDate();
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

        foreach($activitiesByDates as $key => $activitiesByDate) {
            usort($activitiesByDates[$key]['tags'], "\AppBundle\Controller\ActivityController::sortTagByNb");
        }

        $tags = $em->getRepository('AppBundle:Tag')->findAll();

        return $this->render('Activity/list.html.twig', array('activitiesByDates' => $activitiesByDates, 'tags' => $tags, 'query' => $query));
    }

    public static function sortTagByNb($a, $b) {

        return $a['nb'] < $b['nb'];
    }

}
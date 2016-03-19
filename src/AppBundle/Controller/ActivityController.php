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
        $dateFrom = new \DateTime($request->get('date', date('Y-m-d')));
        $query = $request->get('q', null);

        $activitiesByDates = array();

        $dateTo = clone $dateFrom;
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

        $dateNext = null;
        if($activity) {
            $dateNext = new \DateTime($activity->getExecutedAt()->format('Y-m-d'));
            $dateNext = $dateNext->modify("-1 day")->format('Y-m-d');
        }


        return $this->render('Activity/list.html.twig', array('activitiesByDates' => $activitiesByDates, 'tags' => $tags, 'query' => $query, 'dateNext' => $dateNext, 'nbDays' => $nbDays));
    }

    public static function sortTagByNb($a, $b) {

        return $a['nb'] < $b['nb'];
    }

}

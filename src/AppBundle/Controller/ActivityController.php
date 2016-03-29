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
        $am = $this->get('app.manager.activity');
        $repo = $em->getRepository('AppBundle:Activity');

        $nbDays = $request->get('nb', 10);
        $dateFrom = new \DateTime($request->get('date', date('Y-m-d')));
        $query = $request->get('q', null);

        $dateTo = clone $dateFrom;
        $dateTo->modify('-6 month');

        $activities = $repo->findByDatesInterval($dateFrom, $dateTo, $nbDays, $query);

        $tags = $em->getRepository('AppBundle:Tag')->findAll();

        $dateNext = new \DateTime();
        /*if(isset($activity)) {

            $dateNext = new \DateTime($activity->getExecutedAt()->format('Y-m-d'));
            $dateNext = $dateNext->modify("-1 day")->format('Y-m-d');
        }*/

        return $this->render('Activity/list.html.twig', array('activitiesByDates' => $am->createView($activities), 'query' => $query, 'dateNext' => $dateNext, 'nbDays' => $nbDays));
    }

}

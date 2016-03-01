<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="timeline")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Activity');

        $activitiesByDates = array();


        $dateFrom = new \DateTime();
        
        if($request->get('date')) {
            $dateFrom = new \DateTime($request->get('date'). ' 00:00:00');
            $dateFrom->modify('+1 day + 4 hours');    
        }
        
        $dateTo = clone $dateFrom;
        $dateTo->modify('-30 days');

        $tag = "%";
        if($request->get('tag')) {
            $tag = $request->get('tag');
        }

        $activities = $repo->findByDatesInterval($dateFrom, $dateTo, $tag);

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
            usort($activitiesByDates[$key]['tags'], "\AppBundle\Controller\DefaultController::sortTagByNb");
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
